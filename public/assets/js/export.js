/* =========================================================
   APP EXPORT — shared CSV + Excel (.xlsx) downloads
   ---------------------------------------------------------
   One reusable module for every page that needs a data export.

   Programmatic use:
     AppExport.download('csv', {
         filename: 'products',
         columns: ['Name', { label: 'Price', type: 'number' }],
         rows: [['Widget', 12.5], ['Bolt', 3]]
     });

   Button with a CSV / Excel dropdown (renders itself, reuses
   the app's .dropdown-app / .dropdown-menu-app styles):
     AppExport.bind(document.getElementById('exportBtn'), {
         filename: 'products',
         columns: ['Name', 'SKU'],
         getRows: function () { return [['Widget', 'SKU-1']]; }
     });

   columns : string[] or { label, type: 'text' | 'number' }[]
   rows    : array of arrays, values in column order
   ========================================================= */
(function (window, document) {
    'use strict';

    var menus = [];

    /* ---------- Small helpers ---------- */

    function notify(message, icon) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, icon || 'bi-download');
        }
    }

    function dateStamp() {
        var d = new Date();
        var month = d.getMonth() + 1;
        var day = d.getDate();
        return d.getFullYear() + '-' +
            (month < 10 ? '0' : '') + month + '-' +
            (day < 10 ? '0' : '') + day;
    }

    function normalizeColumns(columns) {
        return (columns || []).map(function (column) {
            if (typeof column === 'string') {
                return { label: column, type: 'text' };
            }
            return {
                label: column && column.label != null ? String(column.label) : '',
                type: column && column.type === 'number' ? 'number' : 'text'
            };
        });
    }

    function saveBlob(blob, filename) {
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.setTimeout(function () {
            window.URL.revokeObjectURL(url);
        }, 1000);
    }

    /* ---------- CSV ---------- */

    function csvCell(value) {
        var text = value == null ? '' : String(value);

        /* Neutralize spreadsheet formula injection. */
        if (/^[=+\-@\t\r]/.test(text)) {
            text = "'" + text;
        }

        if (/[",\r\n]/.test(text)) {
            text = '"' + text.replace(/"/g, '""') + '"';
        }
        return text;
    }

    function buildCsvBlob(columns, rows) {
        columns = normalizeColumns(columns);

        var lines = [columns.map(function (column) {
            return csvCell(column.label);
        }).join(',')];

        rows.forEach(function (row) {
            lines.push(columns.map(function (column, index) {
                return csvCell(row[index]);
            }).join(','));
        });

        /* BOM keeps Excel from mangling UTF-8 characters. */
        return new Blob(['\uFEFF' + lines.join('\r\n') + '\r\n'], {
            type: 'text/csv;charset=utf-8;'
        });
    }

    /* ---------- XLSX (dependency-free writer) ---------- */

    var CRC_TABLE = (function () {
        var table = new Uint32Array(256);
        for (var i = 0; i < 256; i++) {
            var c = i;
            for (var k = 0; k < 8; k++) {
                c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
            }
            table[i] = c >>> 0;
        }
        return table;
    })();

    function crc32(bytes) {
        var crc = 0xFFFFFFFF;
        for (var i = 0; i < bytes.length; i++) {
            crc = CRC_TABLE[(crc ^ bytes[i]) & 0xFF] ^ (crc >>> 8);
        }
        return (crc ^ 0xFFFFFFFF) >>> 0;
    }

    function utf8(text) {
        return new TextEncoder().encode(text);
    }

    /* ZIP with stored (uncompressed) entries — enough for .xlsx. */
    function zipStore(entries) {
        var localParts = [];
        var centralParts = [];
        var offset = 0;

        entries.forEach(function (entry) {
            var nameBytes = utf8(entry.name);
            var data = entry.data;
            var crc = crc32(data);

            var local = new Uint8Array(30 + nameBytes.length);
            var ldv = new DataView(local.buffer);
            ldv.setUint32(0, 0x04034b50, true);   /* local header signature */
            ldv.setUint16(4, 20, true);           /* version needed */
            ldv.setUint16(6, 0x0800, true);       /* UTF-8 filename flag */
            ldv.setUint16(8, 0, true);            /* stored */
            ldv.setUint16(10, 0, true);           /* mod time */
            ldv.setUint16(12, 0x0021, true);      /* mod date (1980-01-01) */
            ldv.setUint32(14, crc, true);
            ldv.setUint32(18, data.length, true);
            ldv.setUint32(22, data.length, true);
            ldv.setUint16(26, nameBytes.length, true);
            ldv.setUint16(28, 0, true);
            local.set(nameBytes, 30);

            localParts.push(local, data);

            var central = new Uint8Array(46 + nameBytes.length);
            var cdv = new DataView(central.buffer);
            cdv.setUint32(0, 0x02014b50, true);   /* central header signature */
            cdv.setUint16(4, 20, true);           /* version made by */
            cdv.setUint16(6, 20, true);           /* version needed */
            cdv.setUint16(8, 0x0800, true);       /* UTF-8 filename flag */
            cdv.setUint16(10, 0, true);           /* stored */
            cdv.setUint16(12, 0, true);           /* mod time */
            cdv.setUint16(14, 0x0021, true);      /* mod date */
            cdv.setUint32(16, crc, true);
            cdv.setUint32(20, data.length, true);
            cdv.setUint32(24, data.length, true);
            cdv.setUint16(28, nameBytes.length, true);
            cdv.setUint32(42, offset, true);      /* local header offset */
            central.set(nameBytes, 46);

            centralParts.push(central);
            offset += local.length + data.length;
        });

        var centralSize = 0;
        centralParts.forEach(function (part) { centralSize += part.length; });

        var eocd = new Uint8Array(22);
        var edv = new DataView(eocd.buffer);
        edv.setUint32(0, 0x06054b50, true);       /* end of central dir */
        edv.setUint16(8, entries.length, true);
        edv.setUint16(10, entries.length, true);
        edv.setUint32(12, centralSize, true);
        edv.setUint32(16, offset, true);

        return new Blob(localParts.concat(centralParts, [eocd]), {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        });
    }

    function xmlEscape(value) {
        return String(value == null ? '' : value)
            .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&apos;');
    }

    function toNumber(value) {
        if (typeof value === 'number') {
            return isFinite(value) ? value : null;
        }
        if (typeof value === 'string' && value.trim() !== '') {
            var n = Number(value.replace(/,/g, ''));
            return isFinite(n) ? n : null;
        }
        return null;
    }

    function columnLetter(index) {
        var letters = '';
        var n = index;
        while (n > 0) {
            var remainder = (n - 1) % 26;
            letters = String.fromCharCode(65 + remainder) + letters;
            n = Math.floor((n - 1) / 26);
        }
        return letters;
    }

    function cellXml(ref, value, column, style) {
        var attrs = ' r="' + ref + '"' + (style ? ' s="' + style + '"' : '');

        if (column.type === 'number') {
            var num = toNumber(value);
            if (num !== null) {
                return '<c' + attrs + '><v>' + num + '</v></c>';
            }
        }

        var text = xmlEscape(value);
        if (text.length > 32767) {
            text = text.slice(0, 32767);
        }
        return '<c' + attrs + ' t="inlineStr"><is><t xml:space="preserve">' +
            text + '</t></is></c>';
    }

    function buildSheetXml(columns, rows) {
        var sheetRows = [];

        sheetRows.push('<row r="1">' + columns.map(function (column, index) {
            return cellXml(columnLetter(index + 1) + '1', column.label, column, 1);
        }).join('') + '</row>');

        rows.forEach(function (row, rowIndex) {
            var r = rowIndex + 2;
            sheetRows.push('<row r="' + r + '">' + columns.map(function (column, index) {
                return cellXml(columnLetter(index + 1) + r, row[index], column, 0);
            }).join('') + '</row>');
        });

        var widths = columns.map(function (column, index) {
            var max = column.label ? String(column.label).length : 0;
            rows.forEach(function (row) {
                var length = String(row[index] == null ? '' : row[index]).length;
                if (length > max) max = length;
            });
            return Math.min(50, Math.max(10, max + 2));
        });

        var colsXml = '<cols>' + widths.map(function (width, index) {
            return '<col min="' + (index + 1) + '" max="' + (index + 1) +
                '" width="' + width + '" customWidth="1"/>';
        }).join('') + '</cols>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<sheetViews><sheetView tabSelected="1" workbookViewId="0">' +
            '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>' +
            '</sheetView></sheetViews>' +
            colsXml +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '</worksheet>';
    }

    function buildXlsxBlob(columns, rows) {
        columns = normalizeColumns(columns);

        var contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
            '<Default Extension="xml" ContentType="application/xml"/>' +
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
            '</Types>';

        var rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
            '</Relationships>';

        var workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>' +
            '</workbook>';

        var workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
            '</Relationships>';

        var styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<fonts count="2">' +
            '<font><sz val="11"/><name val="Calibri"/></font>' +
            '<font><b/><sz val="11"/><name val="Calibri"/></font>' +
            '</fonts>' +
            '<fills count="2">' +
            '<fill><patternFill patternType="none"/></fill>' +
            '<fill><patternFill patternType="gray125"/></fill>' +
            '</fills>' +
            '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>' +
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
            '<cellXfs count="2">' +
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
            '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
            '</styleSheet>';

        return zipStore([
            { name: '[Content_Types].xml', data: utf8(contentTypes) },
            { name: '_rels/.rels', data: utf8(rootRels) },
            { name: 'xl/workbook.xml', data: utf8(workbook) },
            { name: 'xl/_rels/workbook.xml.rels', data: utf8(workbookRels) },
            { name: 'xl/styles.xml', data: utf8(styles) },
            { name: 'xl/worksheets/sheet1.xml', data: utf8(buildSheetXml(columns, rows)) }
        ]);
    }

    /* ---------- Public API ---------- */

    function download(format, options) {
        options = options || {};

        var columns = normalizeColumns(options.columns);
        var rows = Array.isArray(options.rows) ? options.rows : [];
        var extension = format === 'xlsx' ? '.xlsx' : '.csv';

        if (!columns.length) {
            notify('No columns configured for export', 'bi-exclamation-circle');
            return false;
        }

        if (!rows.length) {
            notify('Nothing to export', 'bi-exclamation-circle');
            return false;
        }

        var base = options.filename || 'export';
        var filename = base +
            (options.dateStamp === false ? '' : '-' + dateStamp()) +
            extension;

        saveBlob(
            format === 'xlsx' ? buildXlsxBlob(columns, rows) : buildCsvBlob(columns, rows),
            filename
        );

        notify(
            'Exported ' + rows.length + ' row' + (rows.length === 1 ? '' : 's') +
            ' to ' + (format === 'xlsx' ? 'Excel' : 'CSV'),
            'bi-download'
        );
        return true;
    }

    function closeAll() {
        menus.forEach(function (entry) {
            entry.menu.classList.remove('is-open');
            entry.button.setAttribute('aria-expanded', 'false');
        });
    }

    function bind(button, options) {
        if (!button || button.__appExportBound) return null;
        button.__appExportBound = true;

        options = options || {};

        /* Reuse the app's dropdown wrapper so positioning matches filters. */
        var wrap = button.parentNode;
        if (!wrap.classList.contains('dropdown-app')) {
            wrap = document.createElement('div');
            wrap.className = 'dropdown-app';
            button.parentNode.insertBefore(wrap, button);
            wrap.appendChild(button);
        }

        button.setAttribute('aria-haspopup', 'menu');
        button.setAttribute('aria-expanded', 'false');

        if (!button.querySelector('.export-caret')) {
            var caret = document.createElement('i');
            caret.className = 'bi bi-chevron-down export-caret';
            caret.setAttribute('aria-hidden', 'true');
            caret.style.fontSize = '10px';
            caret.style.opacity = '.65';
            button.appendChild(caret);
        }

        var menu = document.createElement('div');
        menu.className = 'dropdown-menu-app export-menu';
        menu.setAttribute('role', 'menu');
        menu.style.minWidth = '200px';
        menu.innerHTML =
            '<div class="dropdown-label-app">Export as</div>' +
            '<button type="button" class="dropdown-item-app" data-export-format="csv">' +
            '<i class="bi bi-filetype-csv" aria-hidden="true"></i>CSV (.csv)</button>' +
            '<button type="button" class="dropdown-item-app" data-export-format="xlsx">' +
            '<i class="bi bi-file-earmark-excel" aria-hidden="true"></i>Excel (.xlsx)</button>';
        wrap.appendChild(menu);

        menus.push({ button: button, menu: menu });

        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var wasOpen = menu.classList.contains('is-open');
            closeAll();
            if (!wasOpen) {
                menu.classList.add('is-open');
                button.setAttribute('aria-expanded', 'true');
            }
        });

        menu.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        menu.querySelectorAll('[data-export-format]').forEach(function (item) {
            item.addEventListener('click', function () {
                var format = item.getAttribute('data-export-format');
                closeAll();
                var rows = typeof options.getRows === 'function' ? options.getRows() : [];
                download(format, {
                    filename: options.filename,
                    columns: options.columns,
                    rows: rows || [],
                    dateStamp: options.dateStamp
                });
            });
        });

        return { button: button, menu: menu };
    }

    document.addEventListener('click', closeAll);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeAll();
    });

    window.AppExport = {
        version: '1.0.0',
        download: download,
        bind: bind,
        csv: function (options) { return download('csv', options); },
        xlsx: function (options) { return download('xlsx', options); },
        /* Exposed for tests/tools; not used by pages. */
        _buildXlsxBlob: buildXlsxBlob,
        _buildCsvBlob: buildCsvBlob
    };
})(window, document);
