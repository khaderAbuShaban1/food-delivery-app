@extends('layouts.restaurant')

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

@if(count($menuItems ?? []) > 0)
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
    @foreach($menuItems as $item)
    <div class="col animate-fade-in" style="animation-delay: {{ $loop->index * 0.1 }}s;">
        <div class="menu-card glass-card h-100">
            <div style="height: 160px; overflow: hidden; position: relative;">
                @if($item['image'])
                    <img src="{{ url('/') }}/storage/{{ $item['image'] }}" alt="{{ $item['name'] }}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                @endif
                <div class="d-flex align-items-center justify-content-center h-100" style="background: var(--bg-card); {{ $item['image'] ? 'display:none;' : '' }}">
                    <i class="bi bi-image" style="font-size: 2.5rem; color: var(--text-muted);"></i>
                </div>
                <span class="menu-price position-absolute" style="bottom: 10px; left: 10px; background: #FFFFFF; padding: 0.375rem 0.75rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                    @price($item['price'])
                </span>
            </div>
            
            <div class="p-3">
                <h5 class="fw-semibold mb-1" style="color: var(--text-primary); font-size: 1rem;">{{ $item['name'] }}</h5>
                <p class="mb-3" style="color: var(--text-secondary); font-size: 0.8rem; line-height: 1.4; min-height: 2.8em; overflow: hidden;">
                    {{ $item['description'] ?? 'لا يوجد وصف' }}
                </p>
                
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline flex-grow-1 edit-btn"
                        data-id="{{ $item['id'] }}"
                        data-name="{{ $item['name'] }}"
                        data-price="{{ $item['price'] }}"
                        data-description="{{ $item['description'] ?? '' }}"
                        data-image="{{ $item['image'] ?? '' }}"
                        data-url="{{ route('restaurant.menu.update', [$restaurant['id'], $item['id']]) }}">
                        <i class="bi bi-pencil me-1"></i>تعديل
                    </button>
                    <form action="{{ route('restaurant.menu.destroy', [$restaurant['id'], $item['id']]) }}" method="POST" class="d-inline flex-grow-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline w-100 text-danger" onclick="return confirm('حذف هذا الصنف؟')">
                            <i class="bi bi-trash me-1"></i>حذف
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="glass-card p-5 text-center">
    <i class="bi bi-bookmark-plus" style="font-size: 4rem; color: var(--text-muted);"></i>
    <h3 class="mt-4 mb-2" style="color: var(--text-primary);">قائمتك فارغة</h3>
    <p class="mb-4" style="color: var(--text-secondary);">أضف أول صنف إلى قائمتك ليبدأ المطعم بالعمل</p>
    <button class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#addMenuModal">
        <i class="bi bi-plus-circle me-2"></i>إضافة صنف جديد
    </button>
</div>
@endif

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
                        <label class="form-label">السعر (₪)</label>
                        <input type="number" name="price" id="editPrice" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" id="editDescription" class="form-control" rows="2"></textarea>
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
                        <label class="form-label">السعر (₪)</label>
                        <input type="number" name="price" class="form-control" placeholder="0.00" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="وصف مختصر..."></textarea>
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
    const editModal = document.getElementById('editMenuModal');
    const editForm = document.getElementById('editForm');
    const editName = document.getElementById('editName');
    const editPrice = document.getElementById('editPrice');
    const editDescription = document.getElementById('editDescription');
    const editCurrentImage = document.getElementById('editCurrentImage');
    const noImagePlaceholder = document.getElementById('noImagePlaceholder');

    document.querySelectorAll('.edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const name = this.dataset.name;
            const price = this.dataset.price;
            const description = this.dataset.description;
            const image = this.dataset.image;
            const url = this.dataset.url;

            editForm.action = url;
            editName.value = name;
            editPrice.value = price;
            editDescription.value = description || '';

            if (image) {
                editCurrentImage.src = '{{ url("/") }}/storage/' + image;
                editCurrentImage.style.display = 'block';
                noImagePlaceholder.style.display = 'none';
            } else {
                editCurrentImage.style.display = 'none';
                noImagePlaceholder.style.display = 'flex';
            }

            const modal = new bootstrap.Modal(editModal);
            modal.show();
        });
    });
});
</script>
@endsection