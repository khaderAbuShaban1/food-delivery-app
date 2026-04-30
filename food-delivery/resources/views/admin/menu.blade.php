@extends('layouts.admin')

@section('title', 'إدارة أصناف القوائم')

@section('styles')
<style>
.filter-bar {
    background: var(--white);
    border-radius: 20px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
}

.status-filters {
    display: flex;
    gap: 0.4rem;
}

.status-btn {
    padding: 0.5rem 0.9rem;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text-muted);
    background: transparent;
    border: 1px solid var(--border);
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'Cairo', sans-serif;
    text-decoration: none;
    white-space: nowrap;
}

.status-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.status-btn.active {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
}

.filter-select {
    padding: 0.5rem 2rem 0.5rem 0.75rem;
    border: 1px solid var(--border);
    border-radius: 25px;
    font-size: 0.85rem;
    outline: none;
    background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%236B7280' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E") no-repeat left 0.75rem center;
    appearance: none;
    cursor: pointer;
    font-family: 'Cairo', sans-serif;
    min-width: 120px;
}

.search-input {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border);
    border-radius: 25px;
    font-size: 0.85rem;
    outline: none;
    font-family: 'Cairo', sans-serif;
    width: 120px;
}

.search-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-muted);
}

.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1.25rem;
    align-items: start;
}

.menu-card {
    background: var(--white);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s;
}

.menu-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.card-header-date {
    padding: 0.75rem 1.25rem;
    background: #FAFAFA;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.card-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
    background: var(--primary-muted);
}

.card-body {
    padding: 1.25rem;
}

.card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.card-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 1rem;
}

.meta-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
}

.meta-label { color: var(--text-muted); }
.meta-value { font-weight: 600; color: var(--text-dark); }

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.available { background: #D1FAE5; color: #059669; }
.status-badge.unavailable { background: #FEE2E2; color: #DC2626; }

.card-actions {
    display: flex;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--border);
}

.btn-card {
    flex: 1;
    padding: 0.5rem;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    font-family: 'Cairo', sans-serif;
    transition: all 0.2s;
}

.btn-edit { background: var(--primary); color: white; }
.btn-edit:hover { background: var(--primary-hover); }
.btn-delete { background: #FEE2E2; color: #DC2626; }
.btn-delete:hover { background: #FECACA; }

.pagination-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-top: 1.5rem;
    background: var(--white);
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    padding: 0.75rem 1rem;
}

.pagination-info {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.pagination-links {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.page-pill {
    min-width: 34px;
    height: 34px;
    padding: 0 0.65rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: #fff;
    color: #4B5563;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
}

.page-pill:hover { border-color: var(--primary); color: var(--primary); }
.page-pill.active { background: var(--primary); border-color: var(--primary); color: #fff; }
.page-pill.disabled { opacity: 0.45; pointer-events: none; }

.modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
.modal.show { display: flex; }
.modal-content { background: var(--white); border-radius: 16px; width: 100%; max-width: 560px; box-shadow: var(--shadow-md); overflow: hidden; }
.modal-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.modal-title { font-size: 1rem; font-weight: 700; color: var(--text-dark); }
.modal-close { background: none; border: none; color: var(--text-muted); font-size: 1.25rem; cursor: pointer; }
.modal-body { padding: 1.25rem; display: grid; gap: 0.9rem; }
.modal-footer { padding: 0.9rem 1.25rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 0.6rem; }
.form-control-lite {
    width: 100%;
    padding: 0.55rem 0.75rem;
    border: 1px solid var(--border);
    border-radius: 10px;
    font-size: 0.85rem;
    outline: none;
}

.form-control-lite:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-muted); }

.btn-modal {
    border: none;
    border-radius: 10px;
    padding: 0.5rem 0.95rem;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
}

.btn-modal.cancel { background: #F3F4F6; color: #4B5563; }
.btn-modal.save { background: var(--primary); color: #fff; }
.btn-modal.danger { background: #DC2626; color: #fff; }

.confirm-message { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; }

@media (max-width: 992px) {
    .filter-row { flex-direction: column; align-items: stretch; }
    .status-filters { width: 100%; overflow-x: auto; }
    .filter-select, .search-input { width: 100%; }
}
</style>
@endsection

@section('content')
<div class="header">
    <h1 class="page-title">إدارة أصناف القوائم</h1>
    <p class="page-subtitle">إدارة وتحديث قائمة الطعام الخاصة بك بسهولة</p>
</div>

<div class="filter-bar">
    <form class="filter-row" method="GET" action="{{ route('admin.menu') }}">
        <div class="status-filters">
            <button type="submit" name="status" value="" class="status-btn {{ !request()->get('status') ? 'active' : '' }}">الكل</button>
            <button type="submit" name="status" value="available" class="status-btn {{ request()->get('status') == 'available' ? 'active' : '' }}">متوفّر</button>
            <button type="submit" name="status" value="unavailable" class="status-btn {{ request()->get('status') == 'unavailable' ? 'active' : '' }}">غير متوفّر</button>
        </div>
        <select class="filter-select" name="category">
            <option value="">جميع الأقسام</option>
            @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ request()->get('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
        </select>
        <select class="filter-select" name="restaurant_id">
            <option value="">جميع المطاعم</option>
            @foreach($restaurants as $r)
            <option value="{{ $r->id }}" {{ request()->get('restaurant_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
            @endforeach
        </select>
        <input type="text" name="search" class="search-input" placeholder="البحث..." value="{{ request()->get('search') }}">
        <button type="submit" class="status-btn active">تطبيق</button>
    </form>
</div>

<div class="menu-grid">
    @forelse($menuItems as $item)
    @php
    $image = $item->image ?? '';
    $imgSrc = $image ? (substr($image, 0, 4) === 'http' ? $image : '/storage/' . $image) : 'https://placehold.co/300x120/FFE8DC/FF6B2C?text=Food';
    @endphp
    <div class="menu-card">
        <div class="card-header-date">
            <span>ID: {{ $item->id_number }}</span>
            <span>{{ $item->updated_at?->format('d/m/Y') }}</span>
        </div>
        <img src="{{ $imgSrc }}" class="card-image" alt="{{ $item->name }}" onerror="this.onerror=null;this.src='https://placehold.co/300x120/FFE8DC/FF6B2C?text=Food'">
        <div class="card-body">
            <h3 class="card-title">{{ $item->name }}</h3>
            <div class="card-meta">
                <div class="meta-row">
                    <span class="meta-label">المطعم</span>
                    <span class="meta-value">{{ $item->restaurant?->name ?? '-' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">السعر</span>
                    <span class="meta-value">@price($item->price)</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">الفئة</span>
                    <span class="meta-value">{{ $item->category ?? '-' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">الحالة</span>
                    <span class="status-badge {{ $item->is_available ? 'available' : 'unavailable' }}">
                        {{ $item->is_available ? 'متوفّر' : 'غير متوفّر' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="card-actions">
            <button
                class="btn-card btn-edit js-edit-item"
                data-id="{{ $item->id }}"
                data-name="{{ $item->name }}"
                data-price="{{ $item->price }}"
                data-category="{{ $item->category }}"
                data-description="{{ $item->description ?? '' }}"
                data-is-available="{{ $item->is_available ? 1 : 0 }}"
            >
                <i class="fas fa-edit"></i> تعديل
            </button>
            <form action="{{ route('admin.menu.destroy', $item->id) }}" method="POST" class="js-delete-form" data-entity-name="{{ $item->name }}">
                @csrf
                <button type="submit" class="btn-card btn-delete">
                    <i class="fas fa-trash"></i> حذف
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="text-center py-5 text-muted" style="grid-column: 1 / -1;">
        <p>لا توجد أصناف</p>
    </div>
    @endforelse

</div>

@if($menuItems->hasPages())
<div class="pagination-bar">
    <span class="pagination-info">عرض {{ $menuItems->firstItem() }} - {{ $menuItems->lastItem() }} من {{ $menuItems->total() }}</span>
    <div class="pagination-links">
        <a href="{{ $menuItems->previousPageUrl() ?: '#' }}" class="page-pill {{ $menuItems->onFirstPage() ? 'disabled' : '' }}">السابق</a>

        @php
            $start = max(1, $menuItems->currentPage() - 2);
            $end = min($menuItems->lastPage(), $menuItems->currentPage() + 2);
        @endphp
        @for($page = $start; $page <= $end; $page++)
            <a href="{{ $menuItems->url($page) }}" class="page-pill {{ $menuItems->currentPage() === $page ? 'active' : '' }}">{{ $page }}</a>
        @endfor

        <a href="{{ $menuItems->nextPageUrl() ?: '#' }}" class="page-pill {{ $menuItems->hasMorePages() ? '' : 'disabled' }}">التالي</a>
    </div>
</div>
@endif

<div class="modal" id="editItemModal" onclick="closeEditModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <span class="modal-title">تعديل الصنف</span>
            <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="editItemForm" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div>
                    <label class="meta-label">الاسم</label>
                    <input class="form-control-lite" type="text" name="name" id="editName" required>
                </div>
                <div>
                    <label class="meta-label">السعر</label>
                    <input class="form-control-lite" type="number" name="price" id="editPrice" min="0" step="0.01" required>
                </div>
                <div>
                    <label class="meta-label">الفئة</label>
                    <input class="form-control-lite" type="text" name="category" id="editCategory" required>
                </div>
                <div>
                    <label class="meta-label">الوصف</label>
                    <textarea class="form-control-lite" name="description" id="editDescription" rows="3"></textarea>
                </div>
                <div>
                    <label class="meta-label">الحالة</label>
                    <select class="form-control-lite" name="is_available" id="editStatus">
                        <option value="1">متوفّر</option>
                        <option value="0">غير متوفّر</option>
                    </select>
                </div>
                <div>
                    <label class="meta-label">الصورة (اختياري)</label>
                    <input class="form-control-lite" type="file" name="image" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal cancel" onclick="closeEditModal()">إلغاء</button>
                <button type="submit" class="btn-modal save">حفظ</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="actionConfirmModal" onclick="closeActionConfirm(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <span class="modal-title" id="actionConfirmTitle">تأكيد الإجراء</span>
            <button type="button" class="modal-close" onclick="closeActionConfirm()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="confirm-message" id="actionConfirmMessage"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal cancel" onclick="closeActionConfirm()">إلغاء</button>
            <button type="button" class="btn-modal" id="actionConfirmButton">تأكيد</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const editModal = document.getElementById('editItemModal');
const editForm = document.getElementById('editItemForm');
const confirmModal = document.getElementById('actionConfirmModal');
const confirmTitle = document.getElementById('actionConfirmTitle');
const confirmMessage = document.getElementById('actionConfirmMessage');
const confirmButton = document.getElementById('actionConfirmButton');
let confirmHandler = null;
let isEditConfirmed = false;

function openActionConfirm(options) {
    confirmTitle.textContent = options.title || 'تأكيد الإجراء';
    confirmMessage.textContent = options.message || 'هل أنت متأكد من تنفيذ هذا الإجراء؟';
    confirmButton.textContent = options.confirmText || 'تأكيد';
    confirmButton.className = `btn-modal ${options.confirmVariant || 'save'}`;
    confirmHandler = options.onConfirm || null;
    confirmModal.classList.add('show');
}

function closeActionConfirm(e) {
    if (e && e.target !== e.currentTarget) return;
    confirmModal.classList.remove('show');
    confirmHandler = null;
}

confirmButton.addEventListener('click', () => {
    if (typeof confirmHandler === 'function') {
        confirmHandler();
    }
    closeActionConfirm();
});

function openEditModal(payload) {
    editForm.action = '/admin/menu/' + payload.id;
    document.getElementById('editName').value = payload.name || '';
    document.getElementById('editPrice').value = payload.price || '';
    document.getElementById('editCategory').value = payload.category || '';
    document.getElementById('editDescription').value = payload.description || '';
    document.getElementById('editStatus').value = String(payload.isAvailable ? 1 : 0);
    editModal.classList.add('show');
}

function closeEditModal(e) {
    if (e && e.target !== e.currentTarget) return;
    editModal.classList.remove('show');
}

document.querySelectorAll('.js-edit-item').forEach((btn) => {
    btn.addEventListener('click', () => {
        openEditModal({
            id: btn.dataset.id,
            name: btn.dataset.name,
            price: btn.dataset.price,
            category: btn.dataset.category,
            description: btn.dataset.description,
            isAvailable: btn.dataset.isAvailable === '1',
        });
    });
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeEditModal();
        closeActionConfirm();
    }
});

document.querySelectorAll('.js-delete-form').forEach((form) => {
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        openActionConfirm({
            title: 'تأكيد حذف التصنيف',
            message: 'هل أنت متأكد من حذف هذا التصنيف؟',
            confirmText: 'حذف',
            confirmVariant: 'danger',
            onConfirm: () => form.submit(),
        });
    });
});

editForm.addEventListener('submit', (e) => {
    if (!isEditConfirmed) {
        e.preventDefault();
        openActionConfirm({
            title: 'تأكيد تعديل التصنيف',
            message: 'هل أنت متأكد من حفظ التغييرات على هذا التصنيف؟',
            confirmText: 'حفظ التغييرات',
            confirmVariant: 'save',
            onConfirm: () => {
                isEditConfirmed = true;
                editForm.requestSubmit();
            },
        });
        return;
    }

    const saveBtn = editForm.querySelector('.btn-modal.save');
    saveBtn.disabled = true;
    saveBtn.textContent = 'جاري الحفظ...';
    isEditConfirmed = false;
});
</script>
@endsection