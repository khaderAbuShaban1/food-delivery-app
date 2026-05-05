@extends('layouts.restaurant')

@section('styles')
<style>
.menu-toolbar { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.65rem; margin-bottom:1rem; }
.toolbar-card { background:#fff; border:1px solid var(--border-subtle); border-radius:14px; padding:.75rem .9rem; box-shadow:var(--shadow-sm); }
.toolbar-label { font-size:.75rem; color:var(--text-secondary); }
.toolbar-value { margin-top:.15rem; font-size:1.1rem; font-weight:700; color:var(--text-primary); }
.menu-filter-bar {
    background: #F7F8FA;
    border: 1px solid #E6E8EC;
    border-radius: 16px;
    padding: .75rem;
    margin-bottom: 1rem;
}
.menu-filter-grid {
    display:grid;
    grid-template-columns: minmax(260px, 1fr) 210px minmax(240px, 280px) 150px;
    gap:.55rem;
    align-items:center;
}
.filter-control {
    min-height: 46px;
    border-radius: 12px;
    border: 1px solid #E4E7EC;
    background: #fff;
    color: #374151;
    font-size: .9rem;
    transition: all .2s ease;
}
.menu-filter-grid .form-control {
    padding: .65rem .875rem;
    line-height: 1.55;
}
.menu-filter-grid .form-select {
    min-height: 46px;
    /* RTL: text from the right, chevron on the left */
    padding: .6rem .875rem .6rem 2.35rem;
    line-height: 1.55;
    overflow: visible;
    background-position: left .75rem center;
}
.filter-control:focus {
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 3px rgba(249, 115, 22, .12);
}
.search-wrap { position: relative; }
.search-wrap i {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
    font-size: .85rem;
}
.search-wrap input { padding-right: 2.1rem; }
.menu-filter-actions { display:flex; justify-content:flex-end; }
.menu-filter-actions .btn {
    height: 44px;
    width: 100%;
    border-radius: 12px;
    border: 1px solid #E4E7EC;
    background: #fff;
    color: #4B5563;
    font-weight: 600;
    font-size: .88rem;
}
.menu-filter-actions .btn:hover {
    border-color: #D1D5DB;
    background: #F9FAFB;
}
.filter-active-indicator { font-size:.78rem; font-weight:700; color:var(--accent-primary); display:none; }
.filter-active-indicator.show { display:inline-flex; align-items:center; gap:.3rem; }
.results-count { font-size:.82rem; color:var(--text-secondary); }
.menu-item-status { position:absolute; top:10px; right:10px; }
.status-chip { border:none; border-radius:999px; padding:.3rem .65rem; font-size:.72rem; font-weight:700; cursor:pointer; }
.status-chip.available { background:rgba(34,197,94,.14); color:#15803D; }
.status-chip.unavailable { background:rgba(239,68,68,.14); color:#B91C1C; }
.status-chip:disabled { opacity:.6; cursor:not-allowed; }
.menu-card .item-actions { margin-top:auto; }

.confirm-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1200; align-items:center; justify-content:center; }
.confirm-modal.show { display:flex; }
.confirm-box { width:100%; max-width:420px; background:#fff; border-radius:16px; box-shadow:var(--shadow-xl); overflow:hidden; }
.confirm-head { padding:1rem 1.1rem; border-bottom:1px solid var(--border-subtle); font-weight:700; }
.confirm-body { padding:1rem 1.1rem; color:var(--text-secondary); font-size:.88rem; }
.confirm-foot { padding:.8rem 1.1rem; border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:.5rem; }
.confirm-btn { border:none; border-radius:10px; padding:.5rem .9rem; font-size:.84rem; font-weight:700; cursor:pointer; }
.confirm-btn.cancel { background:#F3F4F6; color:#4B5563; }
.confirm-btn.danger { background:#DC2626; color:#fff; }
.options-builder { border:1px solid var(--border-subtle); border-radius:12px; padding:.85rem; background:#FAFAFA; }
.options-header { display:flex; justify-content:space-between; align-items:center; gap:.75rem; margin-bottom:.65rem; }
.options-title { font-size:.88rem; font-weight:700; color:var(--text-primary); }
.options-hint { font-size:.75rem; color:var(--text-secondary); }
.option-group-card { border:1px solid var(--border-subtle); border-radius:10px; background:#fff; padding:.75rem; margin-bottom:.65rem; }
.option-group-head { display:grid; grid-template-columns:1fr 160px auto; gap:.5rem; align-items:center; margin-bottom:.55rem; }
.option-values-list { display:flex; flex-direction:column; gap:.45rem; }
.option-value-row { display:grid; grid-template-columns:1fr 130px auto; gap:.45rem; align-items:center; }
.btn-option-add { border:1px dashed var(--accent-primary); background:rgba(249,115,22,.08); color:var(--accent-primary); border-radius:8px; padding:.35rem .6rem; font-size:.78rem; font-weight:700; cursor:pointer; }
.btn-option-remove { border:1px solid #FCA5A5; background:#FEF2F2; color:#B91C1C; border-radius:8px; padding:.32rem .55rem; font-size:.76rem; font-weight:700; cursor:pointer; }
.option-suggestions { display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.55rem; }
.option-suggestion-chip { border:1px solid var(--border-subtle); background:#fff; color:var(--text-secondary); border-radius:999px; padding:.3rem .65rem; font-size:.75rem; cursor:pointer; }
.option-suggestion-chip:hover { border-color:var(--accent-primary); color:var(--accent-primary); }

@media (max-width: 992px) {
    .menu-toolbar { grid-template-columns:1fr; }
    .menu-filter-grid {
        grid-template-columns: minmax(240px, 1fr) 200px minmax(200px, 280px) 140px;
        overflow-x: auto;
        padding-bottom: .2rem;
    }
}
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">إدارة القائمة</h1>
        <p class="page-subtitle">الأصناف والأسعار</p>
    </div>
    <button class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#addMenuModal">
        <i class="bi bi-plus-circle me-2"></i>إضافة صنف جديد
    </button>
</div>

<div class="menu-toolbar">
    <div class="toolbar-card"><div class="toolbar-label">إجمالي الأصناف</div><div class="toolbar-value">{{ count($menuItems ?? []) }}</div></div>
    <div class="toolbar-card"><div class="toolbar-label">الأصناف المتاحة</div><div class="toolbar-value">{{ collect($menuItems ?? [])->where('is_available', true)->count() }}</div></div>
    <div class="toolbar-card"><div class="toolbar-label">الأصناف غير المتاحة</div><div class="toolbar-value">{{ collect($menuItems ?? [])->where('is_available', false)->count() }}</div></div>
</div>

<div class="menu-filter-bar">
    <div class="menu-filter-grid">
        <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" id="filterSearch" class="form-control filter-control" placeholder="ابحث باسم الصنف..." value="{{ $filters['search'] ?? '' }}">
        </div>
        <select id="filterCategory" class="form-select filter-control">
            <option value="">كل الفئات</option>
            @foreach($categories as $key => $label)
                <option value="{{ $key }}" @selected(($filters['category'] ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select id="filterStatus" class="form-select filter-control">
            <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>الكل</option>
            <option value="available" @selected(($filters['status'] ?? '') === 'available')>متاح</option>
            <option value="unavailable" @selected(($filters['status'] ?? '') === 'unavailable')>غير متاح</option>
        </select>
        <div class="menu-filter-actions">
            <button type="button" class="btn btn-outline" id="resetFiltersBtn">إعادة تعيين</button>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <span class="filter-active-indicator" id="activeFilterIndicator"><i class="bi bi-funnel-fill"></i> فلاتر مفعلة</span>
        <span class="results-count">عدد النتائج: <strong id="resultsCount">{{ count($menuItems ?? []) }}</strong></span>
    </div>
</div>

<div id="menuListContainer">
    @include('restaurant::partials.menu-items-grid', ['menuItems' => $menuItems, 'restaurant' => $restaurant, 'categories' => $categories])
</div>

<!-- Single Reusable Edit Modal -->
<div class="modal fade" id="editMenuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>تعديل الصنف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div id="currentImageContainer" class="position-relative d-inline-block">
                            <img id="editCurrentImage" src="" alt="" style="width: 120px; height: 120px; object-fit: cover; border-radius: 12px; display: none;">
                            <div id="noImagePlaceholder" class="d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; background: var(--bg-card); border-radius: 12px;">
                                <i class="bi bi-image" style="font-size: 2rem; color: var(--text-muted);"></i>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تغيير الصورة</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">اتركها فارغة للإبقاء على الصورة الحالية</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم الصنف</label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">السعر ({{ config('app.currency_symbol', '₪') }})</label>
                        <input type="number" name="price" id="editPrice" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الفئة</label>
                        <select name="category" id="editCategory" class="form-select">
                            <option value="">اختر الفئة</option>
                            @foreach($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" id="editDescription" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الحالة</label>
                        <select name="is_available" id="editIsAvailable" class="form-select">
                            <option value="1">متاح</option>
                            <option value="0">غير متاح</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="options-builder">
                            <div class="options-header">
                                <div>
                                    <div class="options-title">الخيارات (اختياري)</div>
                                    <div class="options-hint">مثال: الحجم، الصوص. يمكنك إضافة مجموعات وقيم مع أسعار إضافية.</div>
                                </div>
                                <button type="button" class="btn-option-add" id="editAddOptionGroupBtn">+ إضافة مجموعة</button>
                            </div>
                            <div class="option-groups-container" id="editOptionGroupsContainer"></div>
                            <div class="option-suggestions">
                                <button type="button" class="option-suggestion-chip" data-target="edit" data-suggest-group="الحجم">اقتراح: الحجم</button>
                                <button type="button" class="option-suggestion-chip" data-target="edit" data-suggest-group="الصوص">اقتراح: الصوص</button>
                                <button type="button" class="option-suggestion-chip" data-target="edit" data-suggest-group="الإضافات">اقتراح: الإضافات</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange">
                        <i class="bi bi-check-lg me-1"></i>حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Menu Modal -->
<div class="modal fade" id="addMenuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('restaurant.menu.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>إضافة صنف جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الصورة</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم الصنف</label>
                        <input type="text" name="name" class="form-control" placeholder="مثال: برجر كلاسيك" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">السعر ({{ config('app.currency_symbol', '₪') }})</label>
                        <input type="number" name="price" class="form-control" placeholder="0.00" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الفئة</label>
                        <select name="category" class="form-select">
                            <option value="">اختر الفئة</option>
                            @foreach($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="وصف مختصر..."></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="options-builder">
                            <div class="options-header">
                                <div>
                                    <div class="options-title">الخيارات (اختياري)</div>
                                    <div class="options-hint">أضف فقط ما تحتاجه. لا يوجد خيارات إجبارية.</div>
                                </div>
                                <button type="button" class="btn-option-add" id="addOptionGroupBtn">+ إضافة مجموعة</button>
                            </div>
                            <div class="option-groups-container" id="addOptionGroupsContainer"></div>
                            <div class="option-suggestions">
                                <button type="button" class="option-suggestion-chip" data-target="add" data-suggest-group="الحجم">اقتراح: الحجم</button>
                                <button type="button" class="option-suggestion-chip" data-target="add" data-suggest-group="الصوص">اقتراح: الصوص</button>
                                <button type="button" class="option-suggestion-chip" data-target="add" data-suggest-group="الإضافات">اقتراح: الإضافات</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange">
                        <i class="bi bi-plus-lg me-1"></i>إضافة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="confirm-modal" id="deleteConfirmModal" onclick="closeDeleteConfirm(event)">
    <div class="confirm-box" onclick="event.stopPropagation()">
        <div class="confirm-head">تأكيد الحذف</div>
        <div class="confirm-body">هل أنت متأكد من حذف هذا الصنف؟</div>
        <div class="confirm-foot">
            <button type="button" class="confirm-btn cancel" onclick="closeDeleteConfirm()">إلغاء</button>
            <button type="button" class="confirm-btn danger" id="confirmDeleteBtn">حذف</button>
        </div>
    </div>
</div>

<style>
.menu-card {
    display: flex;
    flex-direction: column;
}

.menu-card > div:first-child {
    flex-shrink: 0;
}

.menu-card .p-3 {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.menu-price {
    color: var(--accent-primary);
    font-weight: 700;
    font-size: 1.1rem;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border-light);
    color: var(--text-secondary);
}

.btn-outline:hover {
    background: var(--bg-card-hover);
    border-color: var(--accent-primary);
    color: var(--accent-primary);
}

.text-danger {
    color: #DC2626 !important;
}

.text-danger:hover {
    background: rgba(239, 68, 68, 0.1);
    border-color: #DC2626;
}

#editMenuModal .modal-content,
#addMenuModal .modal-content {
    border-radius: 16px;
    border: none;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
}

#editMenuModal .modal-header,
#addMenuModal .modal-header {
    border-bottom: 1px solid var(--border-subtle);
    padding: 1.25rem 1.5rem;
}

#editMenuModal .modal-body,
#addMenuModal .modal-body {
    padding: 1.5rem;
}

#editMenuModal .modal-footer,
#addMenuModal .modal-footer {
    border-top: 1px solid var(--border-subtle);
    padding: 1rem 1.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let menuOptionsMap = @json($menuOptionsMap);
    const menuEndpoint = @json(route('restaurant.menu'));
    const editModal = document.getElementById('editMenuModal');
    const editForm = document.getElementById('editForm');
    const editName = document.getElementById('editName');
    const editPrice = document.getElementById('editPrice');
    const editCategory = document.getElementById('editCategory');
    const editIsAvailable = document.getElementById('editIsAvailable');
    const editDescription = document.getElementById('editDescription');
    const editCurrentImage = document.getElementById('editCurrentImage');
    const noImagePlaceholder = document.getElementById('noImagePlaceholder');
    const addForm = document.querySelector('#addMenuModal form');
    const addGroupsContainer = document.getElementById('addOptionGroupsContainer');
    const editGroupsContainer = document.getElementById('editOptionGroupsContainer');
    const menuListContainer = document.getElementById('menuListContainer');
    const filterSearch = document.getElementById('filterSearch');
    const filterCategory = document.getElementById('filterCategory');
    const filterStatus = document.getElementById('filterStatus');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    const resultsCount = document.getElementById('resultsCount');
    const activeFilterIndicator = document.getElementById('activeFilterIndicator');

    function esc(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function emptyGroup() {
        return { name: '', selection_type: 'single', values: [{ name: '', extra_price: 0 }] };
    }

    function addValueRowMarkup(mode, groupIndex, valueIndex, value = {}) {
        return `
            <div class="option-value-row" data-value-index="${valueIndex}">
                <input type="text" class="form-control"
                    name="options[${groupIndex}][values][${valueIndex}][name]"
                    value="${esc(value.name || '')}"
                    placeholder="اسم القيمة (مثال: كبير)">
                <input type="number" class="form-control"
                    name="options[${groupIndex}][values][${valueIndex}][extra_price]"
                    value="${Number(value.extra_price || 0)}"
                    min="0" step="0.01" placeholder="سعر إضافي">
                <button type="button" class="btn-option-remove js-remove-value" data-mode="${mode}">حذف</button>
            </div>
        `;
    }

    function groupCardMarkup(mode, groupIndex, group) {
        const values = Array.isArray(group.values) && group.values.length ? group.values : [{ name: '', extra_price: 0 }];
        return `
            <div class="option-group-card" data-group-index="${groupIndex}">
                <div class="option-group-head">
                    <input type="text" class="form-control"
                        name="options[${groupIndex}][name]"
                        value="${esc(group.name || '')}"
                        placeholder="اسم المجموعة (مثال: الحجم)">
                    <select class="form-select" name="options[${groupIndex}][selection_type]">
                        <option value="single" ${group.selection_type === 'multiple' ? '' : 'selected'}>اختيار واحد (radio)</option>
                        <option value="multiple" ${group.selection_type === 'multiple' ? 'selected' : ''}>اختيارات متعددة (checkbox)</option>
                    </select>
                    <button type="button" class="btn-option-remove js-remove-group" data-mode="${mode}">حذف المجموعة</button>
                </div>
                <div class="option-values-list">
                    ${values.map((value, valueIndex) => addValueRowMarkup(mode, groupIndex, valueIndex, value)).join('')}
                </div>
                <button type="button" class="btn-option-add js-add-value" data-mode="${mode}">+ إضافة قيمة</button>
            </div>
        `;
    }

    function normalizeOptionGroups(rawGroups) {
        if (!Array.isArray(rawGroups)) return [];
        return rawGroups.map(function(group) {
            const values = Array.isArray(group.values) ? group.values : [];
            return {
                name: group.name || '',
                selection_type: group.selection_type === 'multiple' ? 'multiple' : 'single',
                values: values.map(function(value) {
                    return {
                        name: value.name || '',
                        extra_price: Number(value.extra_price || 0),
                    };
                }),
            };
        });
    }

    function renderOptionBuilder(mode, groups) {
        const container = mode === 'add' ? addGroupsContainer : editGroupsContainer;
        container.innerHTML = '';
        groups.forEach(function(group, groupIndex) {
            container.insertAdjacentHTML('beforeend', groupCardMarkup(mode, groupIndex, group));
        });
    }

    function collectGroupsFromDOM(mode) {
        const container = mode === 'add' ? addGroupsContainer : editGroupsContainer;
        return Array.from(container.querySelectorAll('.option-group-card')).map(function(card) {
            const groupNameInput = card.querySelector('input[name*="[name]"]');
            const selectionInput = card.querySelector('select[name*="[selection_type]"]');
            const name = groupNameInput ? groupNameInput.value : '';
            const selection = selectionInput ? selectionInput.value : 'single';
            const values = Array.from(card.querySelectorAll('.option-value-row')).map(function(row) {
                const valueNameInput = row.querySelector('input[name*="[name]"]');
                const extraPriceInput = row.querySelector('input[name*="[extra_price]"]');
                const valueName = valueNameInput ? valueNameInput.value : '';
                const extraPrice = extraPriceInput ? extraPriceInput.value : 0;
                return { name: valueName, extra_price: Number(extraPrice || 0) };
            });
            return { name: name, selection_type: selection, values: values };
        });
    }

    function rerender(mode) {
        renderOptionBuilder(mode, collectGroupsFromDOM(mode));
    }

    function addGroup(mode, seedName = '') {
        const groups = collectGroupsFromDOM(mode);
        const group = emptyGroup();
        group.name = seedName;
        groups.push(group);
        renderOptionBuilder(mode, groups);
    }

    function bindOptionsEvents(container, mode) {
        container.addEventListener('click', function(event) {
            const target = event.target;

            if (target.classList.contains('js-remove-group')) {
                const groupCard = target.closest('.option-group-card');
                if (groupCard) {
                    groupCard.remove();
                }
                rerender(mode);
                return;
            }

            if (target.classList.contains('js-add-value')) {
                const groups = collectGroupsFromDOM(mode);
                const card = target.closest('.option-group-card');
                const groupIndex = Number(card?.dataset.groupIndex ?? -1);
                if (groupIndex >= 0 && groups[groupIndex]) {
                    groups[groupIndex].values.push({ name: '', extra_price: 0 });
                    renderOptionBuilder(mode, groups);
                }
                return;
            }

            if (target.classList.contains('js-remove-value')) {
                const groups = collectGroupsFromDOM(mode);
                const card = target.closest('.option-group-card');
                const row = target.closest('.option-value-row');
                const groupIndex = Number(card?.dataset.groupIndex ?? -1);
                const valueIndex = Number(row?.dataset.valueIndex ?? -1);
                if (groupIndex >= 0 && valueIndex >= 0 && groups[groupIndex]) {
                    groups[groupIndex].values.splice(valueIndex, 1);
                    if (groups[groupIndex].values.length === 0) {
                        groups[groupIndex].values.push({ name: '', extra_price: 0 });
                    }
                    renderOptionBuilder(mode, groups);
                }
            }
        });
    }

    bindOptionsEvents(addGroupsContainer, 'add');
    bindOptionsEvents(editGroupsContainer, 'edit');

    document.getElementById('addOptionGroupBtn').addEventListener('click', function() {
        addGroup('add');
    });

    document.getElementById('editAddOptionGroupBtn').addEventListener('click', function() {
        addGroup('edit');
    });

    document.querySelectorAll('.option-suggestion-chip').forEach(function(btn) {
        btn.addEventListener('click', function() {
            addGroup(this.dataset.target, this.dataset.suggestGroup || '');
        });
    });

    function openEditModal(btn) {
        const itemId = Number(btn.dataset.id);
        const name = btn.dataset.name;
        const price = btn.dataset.price;
        const category = btn.dataset.category;
        const description = btn.dataset.description;
        const image = btn.dataset.image;
        const url = btn.dataset.url;

        editForm.action = url;
        editName.value = name;
        editPrice.value = price;
        editCategory.value = category || '';
        editIsAvailable.value = btn.dataset.isAvailable || '1';
        editDescription.value = description || '';

        if (image) {
            editCurrentImage.src = '{{ asset("storage/") }}/' + image;
            editCurrentImage.style.display = 'block';
            noImagePlaceholder.style.display = 'none';
        } else {
            editCurrentImage.style.display = 'none';
            noImagePlaceholder.style.display = 'flex';
        }

        const existingGroups = normalizeOptionGroups(menuOptionsMap[itemId] || []);
        renderOptionBuilder('edit', existingGroups);

        const modal = new bootstrap.Modal(editModal);
        modal.show();
    }

    addForm.addEventListener('reset', function() {
        setTimeout(function() {
            renderOptionBuilder('add', []);
        }, 0);
    });
    renderOptionBuilder('add', []);

    async function toggleAvailability(btn) {
        if (btn.disabled) return;
        btn.disabled = true;
        try {
            const res = await fetch('/restaurant/menu/' + btn.dataset.id + '/toggle-availability', {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                }
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) throw new Error(data.message || 'تعذر تحديث الحالة');
            btn.classList.toggle('available', !!data.is_available);
            btn.classList.toggle('unavailable', !data.is_available);
            btn.textContent = data.is_available ? btn.dataset.openText : btn.dataset.closeText;
        } catch (error) {
            alert(error.message || 'حدث خطأ');
        } finally {
            btn.disabled = false;
        }
    }

    function updateActiveFilterIndicator() {
        const active = Boolean(filterSearch.value.trim()) || Boolean(filterCategory.value) || filterStatus.value !== 'all';
        activeFilterIndicator.classList.toggle('show', active);
    }

    async function loadFilteredMenu() {
        const params = new URLSearchParams();
        if (filterCategory.value) params.set('category', filterCategory.value);
        if (filterStatus.value) params.set('status', filterStatus.value);
        if (filterSearch.value.trim()) params.set('search', filterSearch.value.trim());

        try {
            const url = params.toString() ? (menuEndpoint + '?' + params.toString()) : menuEndpoint;
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'تعذر تحميل البيانات');
            menuListContainer.innerHTML = data.html || '';
            menuOptionsMap = data.menu_options_map || {};
            resultsCount.textContent = String(data.count || 0);
            updateActiveFilterIndicator();
        } catch (error) {
            alert(error.message || 'حدث خطأ أثناء جلب الأصناف');
        }
    }

    let searchTimer = null;
    filterSearch.addEventListener('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadFilteredMenu, 280);
    });
    filterCategory.addEventListener('change', loadFilteredMenu);
    filterStatus.addEventListener('change', loadFilteredMenu);
    resetFiltersBtn.addEventListener('click', function() {
        filterSearch.value = '';
        filterCategory.value = '';
        filterStatus.value = 'all';
        loadFilteredMenu();
    });
    updateActiveFilterIndicator();

    let pendingDeleteForm = null;
    const deleteModal = document.getElementById('deleteConfirmModal');
    const deleteConfirmBtn = document.getElementById('confirmDeleteBtn');

    window.closeDeleteConfirm = function(e) {
        if (e && e.target !== e.currentTarget) return;
        deleteModal.classList.remove('show');
        pendingDeleteForm = null;
    };

    deleteConfirmBtn.addEventListener('click', function() {
        if (pendingDeleteForm) pendingDeleteForm.submit();
    });

    document.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            openEditModal(editBtn);
            return;
        }

        const toggleBtn = e.target.closest('.js-toggle-availability');
        if (toggleBtn) {
            toggleAvailability(toggleBtn);
            return;
        }

        const resetFromEmpty = e.target.closest('.js-reset-filters-trigger');
        if (resetFromEmpty) {
            resetFiltersBtn.click();
        }
    });

    document.addEventListener('submit', function(e) {
        const deleteForm = e.target.closest('.js-delete-form');
        if (!deleteForm) return;
        e.preventDefault();
        pendingDeleteForm = deleteForm;
        deleteModal.classList.add('show');
    });
});
</script>
@endsection