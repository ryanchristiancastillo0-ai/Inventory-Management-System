# Graph Report - Inventory Management System  (2026-11-09)

## Corpus Check
- 226 files · ~510,726 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 67 file(s) not represented in the graph (top: .css 34, .map 22, (none) 7)

## Summary
- 3324 nodes · 6699 edges · 255 communities (66 shown, 189 thin omitted)
- Extraction: 93% EXTRACTED · 7% INFERRED · 0% AMBIGUOUS · INFERRED: 482 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `6687a2cd`
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
- e
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
- PasswordResetController
- typeCheckConfig
- AuthRepo
- ie
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
- le
- PasswordResetRepo
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
- OrderController
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
- Je
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
- ScrollBarHelper
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
- sn
- BioModal
- theme.js
- orders-ui.js
- ui-overlays.js
- cn
- ProductService
- OrderRepo
- StockMovementController
- reflow
- CategoryController
- CategoryService
- dashboard-users.js
- render
- activity-logs-table.js
- render
- EmailTemplates

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
- `ce()` --indirect_call--> `ae()`  [INFERRED]
  public/assets/bootstrap/js/bootstrap.bundle.min.js → public/assets/face-api/face-api.min.js
- `bo()` --indirect_call--> `D()`  [INFERRED]
  public/assets/face-api/face-api.min.js → public/assets/bootstrap/js/bootstrap.bundle.min.js
- `wo()` --indirect_call--> `D()`  [INFERRED]
  public/assets/face-api/face-api.min.js → public/assets/bootstrap/js/bootstrap.bundle.min.js
- `gt()` --indirect_call--> `it()`  [INFERRED]
  public/assets/bootstrap/js/bootstrap.bundle.min.js → public/assets/face-api/face-api.min.js

## Import Cycles
- None detected.

## Communities (255 total, 189 thin omitted)

### Community 0 - "face-api.min.js"
Cohesion: 0.01
Nodes (32): ad(), bu(), Cf(), cr(), di(), dm(), ed(), en() (+24 more)

### Community 1 - "bootstrap.bundle.js"
Cohesion: 0.05
Nodes (88): addHandler(), applyStyles(), areValidElements(), arrow(), bootstrapDelegationHandler(), bootstrapHandler(), computeAutoPlacement(), computeOffsets() (+80 more)

### Community 2 - "Tooltip"
Cohesion: 0.08
Nodes (3): Popover, sanitizeHtml(), Tooltip

### Community 3 - "bootstrap.esm.min.js"
Cohesion: 0.04
Nodes (53): addHandler(), allowedAttribute(), AttachmentMap, bootstrapDelegationHandler(), bootstrapHandler(), BSCLS_PREFIX_REGEX, BSCLS_PREFIX_REGEX$1, customEvents (+45 more)

### Community 4 - "bootstrap.esm.js"
Cohesion: 0.04
Nodes (51): addHandler(), AttachmentMap, bootstrapDelegationHandler(), bootstrapHandler(), BSCLS_PREFIX_REGEX, BSCLS_PREFIX_REGEX$1, customEvents, Default (+43 more)

### Community 5 - "Tooltip"
Cohesion: 0.08
Nodes (3): Popover, sanitizeHtml(), Tooltip

### Community 7 - "Tooltip"
Cohesion: 0.09
Nodes (3): findShadowRoot(), Popover, Tooltip

### Community 9 - "s"
Cohesion: 0.11
Nodes (37): a(), bo(), ce(), Co(), d(), f(), fm(), h() (+29 more)

### Community 10 - "SmsOtpRepo"
Cohesion: 0.10
Nodes (4): ProfileController, SmsOtpRepo, SmsAuthService, SmsService

### Community 11 - "ReportService"
Cohesion: 0.09
Nodes (4): AdminReportController, ReportController, ReportRepo, ReportService

### Community 13 - "auth/auth.php"
Cohesion: 0.12
Nodes (34): apiRequest(), clearTimers(), closeModal(), detectDescriptor(), ensureCamera(), formatTime(), getChallenge(), getOtpCode() (+26 more)

### Community 14 - "e"
Cohesion: 0.10
Nodes (29): Do(), e(), ft(), hv(), hy(), I(), im(), jd() (+21 more)

### Community 15 - "jt"
Cohesion: 0.12
Nodes (3): It(), Dt(), jt

### Community 16 - "settings/index.php"
Cohesion: 0.09
Nodes (27): apiPost(), beginEnrollment(), captureScan(), renderActions(), renderEnrollError(), renderSlots(), saveEnrollment(), clearOtp() (+19 more)

### Community 20 - "bootstrap.js"
Cohesion: 0.10
Nodes (21): addHandler(), bootstrapDelegationHandler(), bootstrapHandler(), findHandler(), getDataAttribute(), getDataAttributes(), getEvent(), getTypeEvent() (+13 more)

### Community 21 - "Dropdown"
Cohesion: 0.10
Nodes (8): Dropdown, getElement(), getNextActiveElement(), isDisabled(), isElement(), isVisible(), noop(), typeCheckConfig()

### Community 22 - "nn"
Cohesion: 0.08
Nodes (45): ag(), am(), ao(), Av(), bv(), Cg(), cv(), Cx() (+37 more)

### Community 26 - "PasswordResetController"
Cohesion: 0.21
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), PasswordResetController

### Community 27 - "typeCheckConfig"
Cohesion: 0.16
Nodes (8): allowedAttribute(), getElement(), getNextActiveElement(), isElement(), isVisible(), sanitizeHtml(), toType(), typeCheckConfig()

### Community 28 - "AuthRepo"
Cohesion: 0.06
Nodes (6): FaceController, PasskeyController, AuthRepo, FaceRepo, FaceService, PasskeyService

### Community 29 - "ie"
Cohesion: 0.14
Nodes (14): ba(), Ee(), he(), ie(), je(), na(), oe(), pe() (+6 more)

### Community 30 - "bootstrap.bundle.min.js"
Cohesion: 0.14
Nodes (27): At(), bt(), ce(), Ct(), dt(), Et(), ft(), gt() (+19 more)

### Community 33 - "bootstrap.min.js"
Cohesion: 0.13
Nodes (18): getDataAttributes(), H(), j(), K(), M(), off(), on(), one() (+10 more)

### Community 36 - "project.yml"
Cohesion: 0.08
Nodes (25): activation_command, activation_command_timeout, added_modes, agent_interface, default_modes, encoding, excluded_apis, excluded_tools (+17 more)

### Community 37 - "ActivityLogService"
Cohesion: 0.14
Nodes (3): AdminActivityLogController, ActivityLogRepo, ActivityLogService

### Community 38 - "AuthService"
Cohesion: 0.24
Nodes (3): AuthMiddleware, RoleMiddleware, AuthService

### Community 39 - "OrderHistoryService"
Cohesion: 0.10
Nodes (3): OrderHistoryController, OrderHistoryRepo, OrderHistoryService

### Community 42 - "le"
Cohesion: 0.10
Nodes (24): aa(), ae(), be(), ca(), de(), ea(), fe(), ga() (+16 more)

### Community 43 - "PasswordResetRepo"
Cohesion: 0.16
Nodes (4): NotificationController, PasswordResetRepo, NotificationService, PasswordResetService

### Community 46 - "Collapse"
Cohesion: 0.23
Nodes (4): Collapse, getElementFromSelector(), getSelector(), getSelectorFromElement()

### Community 47 - "Dropdown"
Cohesion: 0.14
Nodes (3): Dropdown, isDisabled(), noop()

### Community 51 - "Modal"
Cohesion: 0.13
Nodes (3): Modal, reflow(), Tab

### Community 56 - "BaseComponent"
Cohesion: 0.12
Nodes (3): Alert, BaseComponent, Button

### Community 57 - "ProductRepo"
Cohesion: 0.14
Nodes (3): AdminDashboardController, ProductRepo, AdminDashboardService

### Community 59 - "oi"
Cohesion: 0.08
Nodes (26): ai(), as(), bn(), ei(), fa(), gi(), ii(), is() (+18 more)

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
Cohesion: 0.20
Nodes (4): ee(), nt, it(), ot()

### Community 80 - "orders-table.js"
Cohesion: 0.43
Nodes (6): filteredRows(), render(), renderPagination(), btn(), setSearch(), syncInputs()

### Community 82 - "NotificationService"
Cohesion: 0.11
Nodes (3): NotificationRepo, EmailService, {closure#1}()

### Community 83 - "stock-movement-table.js"
Cohesion: 0.46
Nodes (7): filteredRows(), render(), renderPagination(), btn(), setSearch(), setType(), syncInputs()

### Community 95 - "N"
Cohesion: 0.21
Nodes (12): D(), I(), j(), M(), N(), off(), on(), one() (+4 more)

### Community 96 - "profile.js"
Cohesion: 0.18
Nodes (5): clearError(), formatTime(), resetOtp(), showError(), startExpiry()

### Community 98 - "BaseComponent"
Cohesion: 0.12
Nodes (3): BaseComponent, Button, Tab

### Community 106 - "AdminUserController"
Cohesion: 0.07
Nodes (3): UserRepo, AdminUserService, ProfileService

### Community 107 - "history-table.js"
Cohesion: 0.52
Nodes (6): filteredRows(), render(), renderPagination(), btn(), setSearch(), syncInputs()

### Community 109 - "Backdrop"
Cohesion: 0.33
Nodes (5): Backdrop, execute(), executeAfterTransition(), getTransitionDurationFromElement(), triggerTransitionEnd()

### Community 110 - "Backdrop"
Cohesion: 0.33
Nodes (5): Backdrop, execute(), executeAfterTransition(), getTransitionDurationFromElement(), triggerTransitionEnd()

### Community 118 - "composer.json"
Cohesion: 0.20
Nodes (9): authors, autoload, psr-4, name, App\\, require, getbrevo/brevo-php, guzzlehttp/guzzle (+1 more)

### Community 130 - "reset-password.php"
Cohesion: 0.33
Nodes (5): isLengthValid(), isMatchValid(), paintRequirement(), validateConfirm(), validatePassword()

### Community 133 - "tg"
Cohesion: 0.47
Nodes (6): tg(), a(), n(), o(), r(), wg()

### Community 134 - "settings-preferences.js"
Cohesion: 0.67
Nodes (5): readPreferences(), render(), save(), setStatus(), update()

### Community 135 - "sn"
Cohesion: 0.10
Nodes (28): an(), b(), dn(), ep(), fn(), Gm(), Hm(), j() (+20 more)

### Community 141 - "orders-ui.js"
Cohesion: 0.83
Nodes (3): closeAll(), openDropdown(), resetMenu()

### Community 153 - "ui-overlays.js"
Cohesion: 0.41
Nodes (11): clear(), floatingClass(), init(), isCustom(), isFloating(), isOpen(), place(), repositionOpen() (+3 more)

### Community 202 - "cn"
Cohesion: 0.09
Nodes (33): cn(), gu(), ho(), hr(), In(), ko(), ln(), mc() (+25 more)

### Community 203 - "ProductService"
Cohesion: 0.13
Nodes (4): DashboardController, InventoryController, ProductService, StockMovementService

### Community 216 - "dashboard-users.js"
Cohesion: 0.52
Nodes (6): filteredRows(), render(), renderPagination(), btn(), setSearch(), syncInputs()

### Community 217 - "render"
Cohesion: 0.60
Nodes (5): filteredRows(), render(), renderPagination(), btn(), setSearch()

### Community 246 - "activity-logs-table.js"
Cohesion: 0.60
Nodes (5): filteredRows(), render(), setSearch(), syncPaginationLinks(), syncUrl()

### Community 247 - "render"
Cohesion: 0.60
Nodes (5): filteredRows(), render(), renderPagination(), btn(), setSearch()

### Community 248 - "EmailTemplates"
Cohesion: 0.33
Nodes (3): EmailTemplates, {closure#1}(), {closure#2}()

## Knowledge Gaps
- **189 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `AuthService` connect `AuthService` to `PasswordResetController`, `ActivityLogService`, `AuthController`, `OrderHistoryService`, `.render`, `AdminUserController`, `ReportService`, `SmsOtpRepo`, `ProductService`, `ProductController`, `SettingsController`, `SettingsRepo`, `RuntimeException`, `OrderController`, `AdminUserController`, `AuthRepo`?**
  _High betweenness centrality (0.013) - this node is a cross-community bridge._
- **Are the 20 inferred relationships involving `e()` (e.g. with `d()` and `u()`) actually correct?**
  _`e()` has 20 INFERRED edges - model-reasoned connections that need verification._
- **Should `face-api.min.js` be split into smaller, more focused modules?**
  _Cohesion score 0.013709005529046428 - nodes in this community are weakly interconnected._
- **Why does `f()` connect `s` to `face-api.min.js`, `pt`, `e`, `jt`, `nn`?**
  _High betweenness centrality (0.012) - this node is a cross-community bridge._
- **Should `bootstrap.bundle.js` be split into smaller, more focused modules?**
  _Cohesion score 0.052525252525252523 - nodes in this community are weakly interconnected._
- **Why does `u()` connect `s` to `face-api.min.js`, `Fe`, `Ae`, `e`, `nn`?**
  _High betweenness centrality (0.008) - this node is a cross-community bridge._
- **Should `Tooltip` be split into smaller, more focused modules?**
  _Cohesion score 0.0824829931972789 - nodes in this community are weakly interconnected._