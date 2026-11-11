# Graph Report - Inventory Management System  (2026-11-11)

## Corpus Check
- 243 files · ~516,098 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 71 file(s) not represented in the graph (top: .css 35, .map 22, (none) 9)

## Summary
- 3357 nodes · 6748 edges · 268 communities (66 shown, 202 thin omitted)
- Extraction: 93% EXTRACTED · 7% INFERRED · 0% AMBIGUOUS · INFERRED: 488 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `8ec4c511`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- face-api.min.js
- bootstrap.bundle.js
- Tooltip
- bootstrap.esm.min.js
- bootstrap.esm.js
- Tooltip
- Tooltip
- Tooltip
- Fe
- s
- SmsOtpRepo
- ReportService
- Carousel
- auth/auth.php
- cn
- jt
- settings/index.php
- RuntimeException
- PDO
- bootstrap.js
- Dropdown
- nn
- Carousel
- Carousel
- wt
- getOffsetParent
- OrderController
- AuthRepo
- le
- bootstrap.bundle.min.js
- Z
- Carousel
- bootstrap.min.js
- et
- pt
- project.yml
- ActivityLogService
- AuthService
- OrderHistoryService
- ScrollSpy
- ScrollBarHelper
- export.js
- Dropdown
- Ae
- Collapse
- Dropdown
- Offcanvas
- Modal
- Modal
- Modal
- ft
- De
- Modal
- OrderService
- BaseComponent
- ProductRepo
- AdminUserController
- oi
- SettingsRepo
- BaseComponent
- B
- BaseComponent
- q
- StockMovementRepo
- products-catalog.js
- Collapse
- OrderItemRepo
- AuthController
- SupplierController
- et
- ProductController
- ScrollSpy
- nt
- orders-table.js
- UserService
- NotificationService
- stock-movement-table.js
- ._queueCallback
- Collapse
- preventOverflow
- ni
- Toast
- Collapse
- Toast
- Toast
- Qt
- ScrollSpy
- Toast
- N
- profile.js
- BaseComponent
- Ne
- Offcanvas
- Offcanvas
- ScrollSpy
- Tt
- AdminUserController
- history-table.js
- SettingsController
- Backdrop
- Backdrop
- Te
- ScrollBarHelper
- ScrollBarHelper
- Popover
- composer.json
- Dropdown
- Alert
- Backdrop
- Alert
- Backdrop
- ke
- W
- Env
- bt
- z
- reset-password.php
- SupplierService
- tg
- settings-preferences.js
- Hm
- BioModal
- theme.js
- orders-ui.js
- ui-overlays.js
- sn
- ProductService
- OrderRepo
- StockMovementController
- SmsAuthService
- detectOverflow
- AdminUserService
- ReportController
- CategoryService
- dashboard-users.js
- render
- popperGenerator
- InventoryController
- activity-logs-table.js
- render
- AdminDashboardService
- hg
- sw.js

## God Nodes (most connected - your core abstractions)
1. `AuthService` - 65 edges
2. `UserRepo` - 43 edges
3. `e()` - 43 edges
4. `Tooltip` - 38 edges
5. `Tooltip` - 38 edges
6. `Tooltip` - 38 edges
7. `jt` - 37 edges
8. `Tooltip` - 37 edges
9. `Fe` - 37 edges
10. `t()` - 37 edges

## Surprising Connections (you probably didn't know these)
- `ft()` --indirect_call--> `ht()`  [INFERRED]
  public/assets/face-api/face-api.min.js → public/assets/bootstrap/js/bootstrap.bundle.min.js
- `gt()` --indirect_call--> `it()`  [INFERRED]
  public/assets/bootstrap/js/bootstrap.bundle.min.js → public/assets/face-api/face-api.min.js
- `ie()` --indirect_call--> `re()`  [INFERRED]
  public/assets/face-api/face-api.min.js → public/assets/bootstrap/js/bootstrap.bundle.min.js
- `Ts()` --indirect_call--> `Kt()`  [INFERRED]
  public/assets/face-api/face-api.min.js → public/assets/bootstrap/js/bootstrap.bundle.min.js
- `ce()` --indirect_call--> `ae()`  [INFERRED]
  public/assets/bootstrap/js/bootstrap.bundle.min.js → public/assets/face-api/face-api.min.js

## Import Cycles
- None detected.

## Communities (268 total, 202 thin omitted)

### Community 0 - "face-api.min.js"
Cohesion: 0.01
Nodes (39): ad(), bn(), bu(), Cf(), cr(), Cx(), di(), dm() (+31 more)

### Community 1 - "bootstrap.bundle.js"
Cohesion: 0.10
Nodes (25): addHandler(), bootstrapDelegationHandler(), bootstrapHandler(), findHandler(), getDataAttribute(), getDataAttributes(), getEvent(), getFreshSideObject() (+17 more)

### Community 2 - "Tooltip"
Cohesion: 0.08
Nodes (3): Popover, sanitizeHtml(), Tooltip

### Community 3 - "bootstrap.esm.min.js"
Cohesion: 0.04
Nodes (50): addHandler(), AttachmentMap, bootstrapDelegationHandler(), bootstrapHandler(), BSCLS_PREFIX_REGEX, BSCLS_PREFIX_REGEX$1, customEvents, Default (+42 more)

### Community 4 - "bootstrap.esm.js"
Cohesion: 0.04
Nodes (51): addHandler(), AttachmentMap, bootstrapDelegationHandler(), bootstrapHandler(), BSCLS_PREFIX_REGEX, BSCLS_PREFIX_REGEX$1, customEvents, Default (+43 more)

### Community 5 - "Tooltip"
Cohesion: 0.08
Nodes (3): Popover, sanitizeHtml(), Tooltip

### Community 6 - "Tooltip"
Cohesion: 0.10
Nodes (6): allowedAttribute(), findShadowRoot(), getElement(), noop(), sanitizeHtml(), Tooltip

### Community 7 - "Tooltip"
Cohesion: 0.08
Nodes (4): findShadowRoot(), Popover, Tooltip, triggerTransitionEnd()

### Community 9 - "s"
Cohesion: 0.09
Nodes (55): a(), bo(), ce(), d(), Do(), e(), f(), fm() (+47 more)

### Community 10 - "SmsOtpRepo"
Cohesion: 0.14
Nodes (3): ProfileController, SmsOtpRepo, SmsService

### Community 11 - "ReportService"
Cohesion: 0.11
Nodes (3): AdminReportController, ReportRepo, ReportService

### Community 13 - "auth/auth.php"
Cohesion: 0.12
Nodes (34): apiRequest(), clearTimers(), closeModal(), detectDescriptor(), ensureCamera(), formatTime(), getChallenge(), getOtpCode() (+26 more)

### Community 14 - "cn"
Cohesion: 0.08
Nodes (33): cn(), Co(), gu(), ho(), hr(), In(), ko(), ln() (+25 more)

### Community 16 - "settings/index.php"
Cohesion: 0.09
Nodes (27): apiPost(), beginEnrollment(), captureScan(), renderActions(), renderEnrollError(), renderSlots(), saveEnrollment(), clearOtp() (+19 more)

### Community 17 - "RuntimeException"
Cohesion: 0.12
Nodes (5): View, ProfileValidationException, EmailTemplates, {closure#1}(), {closure#2}()

### Community 20 - "bootstrap.js"
Cohesion: 0.11
Nodes (20): addHandler(), bootstrapDelegationHandler(), bootstrapHandler(), findHandler(), getDataAttribute(), getDataAttributes(), getEvent(), getTypeEvent() (+12 more)

### Community 22 - "nn"
Cohesion: 0.07
Nodes (56): ag(), am(), ao(), Av(), bv(), Cg(), cv(), dv() (+48 more)

### Community 26 - "getOffsetParent"
Cohesion: 0.22
Nodes (21): applyStyles(), contains(), effect$1(), effect$2(), getClippingParents(), getComputedStyle$1(), getContainingBlock(), getNodeName() (+13 more)

### Community 28 - "AuthRepo"
Cohesion: 0.05
Nodes (6): FaceController, PasskeyController, AuthRepo, FaceRepo, FaceService, PasskeyService

### Community 29 - "le"
Cohesion: 0.06
Nodes (33): ba(), be(), de(), ea(), Ee(), fa(), fe(), ft() (+25 more)

### Community 30 - "bootstrap.bundle.min.js"
Cohesion: 0.14
Nodes (28): At(), bt(), ce(), Ct(), dt(), Et(), ft(), gt() (+20 more)

### Community 33 - "bootstrap.min.js"
Cohesion: 0.14
Nodes (17): getDataAttributes(), H(), j(), K(), M(), off(), on(), one() (+9 more)

### Community 36 - "project.yml"
Cohesion: 0.08
Nodes (25): activation_command, activation_command_timeout, added_modes, agent_interface, default_modes, encoding, excluded_apis, excluded_tools (+17 more)

### Community 37 - "ActivityLogService"
Cohesion: 0.13
Nodes (3): AdminActivityLogController, ActivityLogRepo, ActivityLogService

### Community 38 - "AuthService"
Cohesion: 0.23
Nodes (3): AuthMiddleware, RoleMiddleware, AuthService

### Community 39 - "OrderHistoryService"
Cohesion: 0.10
Nodes (3): OrderHistoryController, OrderHistoryRepo, OrderHistoryService

### Community 42 - "ScrollBarHelper"
Cohesion: 0.21
Nodes (4): getNextActiveElement(), isElement(), isVisible(), ScrollBarHelper

### Community 43 - "export.js"
Cohesion: 0.22
Nodes (18): bind(), buildCsvBlob(), buildSheetXml(), buildXlsxBlob(), cellXml(), closeAll(), columnLetter(), crc32() (+10 more)

### Community 46 - "Collapse"
Cohesion: 0.15
Nodes (6): Collapse, getElementFromSelector(), getSelector(), getSelectorFromElement(), reflow(), Tab

### Community 47 - "Dropdown"
Cohesion: 0.09
Nodes (11): allowedAttribute(), Dropdown, getElement(), getNextActiveElement(), isDisabled(), isElement(), isVisible(), noop() (+3 more)

### Community 56 - "BaseComponent"
Cohesion: 0.12
Nodes (3): Alert, BaseComponent, Button

### Community 59 - "oi"
Cohesion: 0.08
Nodes (29): aa(), ae(), ai(), as(), ca(), ei(), ia(), ii() (+21 more)

### Community 61 - "BaseComponent"
Cohesion: 0.12
Nodes (3): BaseComponent, Button, Tab

### Community 62 - "B"
Cohesion: 0.11
Nodes (5): B, getDataAttributes(), q, ti, z()

### Community 63 - "BaseComponent"
Cohesion: 0.12
Nodes (3): Alert, BaseComponent, Button

### Community 64 - "q"
Cohesion: 0.12
Nodes (3): F, q, Ut

### Community 66 - "products-catalog.js"
Cohesion: 0.26
Nodes (9): applyFilters(), openAdd(), openEdit(), refreshBulkBar(), refreshSelectAll(), resetPhotoPicker(), setPhotoState(), showFormError() (+1 more)

### Community 67 - "Collapse"
Cohesion: 0.23
Nodes (4): Collapse, getElementFromSelector(), getSelector(), getSelectorFromElement()

### Community 76 - "nt"
Cohesion: 0.19
Nodes (4): ee(), nt, it(), ot()

### Community 80 - "orders-table.js"
Cohesion: 0.43
Nodes (6): filteredRows(), render(), renderPagination(), btn(), setSearch(), syncInputs()

### Community 82 - "NotificationService"
Cohesion: 0.05
Nodes (13): NotificationController, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), PasswordResetController, NotificationRepo (+5 more)

### Community 83 - "stock-movement-table.js"
Cohesion: 0.46
Nodes (7): filteredRows(), render(), renderPagination(), btn(), setSearch(), setType(), syncInputs()

### Community 86 - "preventOverflow"
Cohesion: 0.18
Nodes (18): arrow(), computeAutoPlacement(), computeOffsets(), computeStyles(), distanceAndSkiddingToXY(), flip(), getAltAxis(), getBasePlacement() (+10 more)

### Community 90 - "Toast"
Cohesion: 0.16
Nodes (3): reflow(), Tab, Toast

### Community 95 - "N"
Cohesion: 0.24
Nodes (11): D(), I(), j(), M(), N(), off(), on(), one() (+3 more)

### Community 96 - "profile.js"
Cohesion: 0.18
Nodes (5): clearError(), formatTime(), resetOtp(), showError(), startExpiry()

### Community 98 - "BaseComponent"
Cohesion: 0.12
Nodes (3): BaseComponent, Button, Tab

### Community 107 - "history-table.js"
Cohesion: 0.52
Nodes (6): filteredRows(), render(), renderPagination(), btn(), setSearch(), syncInputs()

### Community 109 - "Backdrop"
Cohesion: 0.35
Nodes (4): Backdrop, execute(), executeAfterTransition(), getTransitionDurationFromElement()

### Community 110 - "Backdrop"
Cohesion: 0.31
Nodes (5): Backdrop, execute(), executeAfterTransition(), getTransitionDurationFromElement(), typeCheckConfig()

### Community 118 - "composer.json"
Cohesion: 0.15
Nodes (12): php-http/discovery, authors, autoload, psr-4, config, allow-plugins, name, App\\ (+4 more)

### Community 130 - "reset-password.php"
Cohesion: 0.33
Nodes (5): isLengthValid(), isMatchValid(), paintRequirement(), validateConfirm(), validatePassword()

### Community 133 - "tg"
Cohesion: 0.47
Nodes (6): tg(), a(), n(), o(), r(), wg()

### Community 134 - "settings-preferences.js"
Cohesion: 0.67
Nodes (5): readPreferences(), render(), save(), setStatus(), update()

### Community 135 - "Hm"
Cohesion: 0.40
Nodes (5): Gm(), Hm(), Wm(), yd(), zp()

### Community 141 - "orders-ui.js"
Cohesion: 0.83
Nodes (3): closeAll(), openDropdown(), resetMenu()

### Community 153 - "ui-overlays.js"
Cohesion: 0.41
Nodes (11): clear(), floatingClass(), init(), isCustom(), isFloating(), isOpen(), place(), repositionOpen() (+3 more)

### Community 202 - "sn"
Cohesion: 0.20
Nodes (15): an(), b(), dn(), ep(), fn(), j(), m(), q() (+7 more)

### Community 209 - "detectOverflow"
Cohesion: 0.25
Nodes (15): detectOverflow(), expandToHashMap(), getBoundingClientRect(), getClientRectFromMixedType(), getClippingRect(), getCompositeRect(), getDocumentElement(), getDocumentRect() (+7 more)

### Community 215 - "CategoryService"
Cohesion: 0.11
Nodes (3): CategoryController, CategoryRepo, CategoryService

### Community 216 - "dashboard-users.js"
Cohesion: 0.52
Nodes (6): filteredRows(), render(), renderPagination(), btn(), setSearch(), syncInputs()

### Community 217 - "render"
Cohesion: 0.60
Nodes (5): filteredRows(), render(), renderPagination(), btn(), setSearch()

### Community 219 - "popperGenerator"
Cohesion: 0.20
Nodes (9): areValidElements(), debounce(), effect(), mergeByName(), order(), sort(), orderModifiers(), popperGenerator() (+1 more)

### Community 246 - "activity-logs-table.js"
Cohesion: 0.60
Nodes (5): filteredRows(), render(), setSearch(), syncPaginationLinks(), syncUrl()

### Community 247 - "render"
Cohesion: 0.60
Nodes (5): filteredRows(), render(), renderPagination(), btn(), setSearch()

### Community 265 - "hg"
Cohesion: 0.50
Nodes (5): hg(), h(), l(), Ov(), zv()

## Knowledge Gaps
- **202 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `AuthService` connect `AuthService` to `OrderController.php`, `SmsOtpRepo`, `ReportService`, `RuntimeException`, `OrderController`, `AuthRepo`, `ActivityLogService`, `OrderHistoryService`, `AdminUserController`, `SettingsRepo`, `AuthController`, `.render`, `ProductController`, `ProductService`, `SmsAuthService`, `NotificationService`, `AdminUserService`, `ReportController`, `SettingsController`, `InventoryController`?**
  _High betweenness centrality (0.018) - this node is a cross-community bridge._
- **Are the 20 inferred relationships involving `e()` (e.g. with `d()` and `u()`) actually correct?**
  _`e()` has 20 INFERRED edges - model-reasoned connections that need verification._
- **Should `face-api.min.js` be split into smaller, more focused modules?**
  _Cohesion score 0.01336581970066133 - nodes in this community are weakly interconnected._
- **Why does `f()` connect `s` to `face-api.min.js`, `pt`, `nn`, `jt`?**
  _High betweenness centrality (0.011) - this node is a cross-community bridge._
- **Should `bootstrap.bundle.js` be split into smaller, more focused modules?**
  _Cohesion score 0.09523809523809523 - nodes in this community are weakly interconnected._
- **Why does `UserRepo` connect `AdminUserController` to `.getDashboardStats`, `.connect`, `ReportService`, `SettingsController`, `RuntimeException`, `NotificationService`, `PDO`, `AdminUserService`, `UserService`, `CategoryService`, `AdminDashboardService`, `SettingsRepo`?**
  _High betweenness centrality (0.011) - this node is a cross-community bridge._
- **Should `Tooltip` be split into smaller, more focused modules?**
  _Cohesion score 0.0824829931972789 - nodes in this community are weakly interconnected._