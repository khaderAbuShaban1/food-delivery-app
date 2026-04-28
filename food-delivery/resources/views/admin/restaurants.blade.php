@extends('layouts.admin')

@section('title', 'إدارة المطاعم')

@section('styles')
<style>
.filter-tabs { display: flex; gap: 0.5rem; padding: 0.5rem; background: var(--white); border-radius: 12px; flex-wrap: wrap; box-shadow: var(--shadow-sm); }
.filter-tab { padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem; color: var(--text-muted); font-weight: 500; cursor: pointer; border: none; background: transparent; font-family: 'Cairo', sans-serif; }
.filter-tab:hover, .filter-tab.active { background: var(--primary-muted); color: var(--primary); }
.data-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.table { margin: 0; }
.table th { background: var(--bg-page); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); padding: 0.875rem 1rem; border: none; white-space: nowrap; }
.table td { padding: 0.875rem 1rem; border-color: var(--border); vertical-align: middle; font-size: 0.9rem; }
.table tr:hover { background: var(--bg-page); }
.restaurant-name { font-weight: 600; display: flex; align-items: center; gap: 0.75rem; }
.restaurant-icon { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--primary-light)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem; object-fit: cover; }
.rating { display: flex; align-items: center; gap: 0.375rem; }
.rating-count { font-size: 0.8rem; color: var(--text-muted); }
.badge { padding: 0.25rem 0.625rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
.badge-success { background: #D1FAE5; color: #059669; }
.badge-danger { background: #FEE2E2; color: #DC2626; }
.badge-outline-success { background: transparent; border: 1px solid #059669; color: #059669; }
.badge-outline-danger { background: transparent; border: 1px solid #DC2626; color: #DC2626; }
.status-badge { display: inline-flex; align-items: center; gap: 0.375rem; min-width: 90px; justify-content: center; cursor: pointer; }
.status-badge:hover { background: #F3F4F6; transform: scale(1.02); transition: all 0.2s; }
.btn-action { padding: 0.375rem 0.75rem; border-radius: 8px; font-size: 0.8rem; border: 1px solid var(--border); background: var(--white); color: var(--text-muted); cursor: pointer; transition: all 0.2s; margin-left: 0.5rem; }
.btn-action:hover { background: #F9FAFB; color: var(--text-dark); }
.btn-edit { display: inline-flex; align-items: center; gap: 0.375rem; }
.btn-delete { display: inline-flex; align-items: center; gap: 0.375rem; }
.btn-delete:hover { background: #FEE2E2; border-color: #DC2626; color: #DC2626; }
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
.modal.show { display: flex; }
.modal-content { background: var(--white); border-radius: 16px; width: 100%; max-width: 500px; box-shadow: var(--shadow-md); }
.modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.modal-title { font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
.modal-close { background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted); }
.modal-body { padding: 1.5rem; }
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem; }
.form-control { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--border); border-radius: 8px; font-family: 'Cairo', sans-serif; font-size: 0.9rem; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-muted); }
.modal-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 0.75rem; }
.btn { padding: 0.625rem 1.25rem; border-radius: 8px; font-family: 'Cairo', sans-serif; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-secondary { background: var(--white); border: 1px solid var(--border); color: var(--text-muted); }
.btn-secondary:hover { background: var(--bg-page); }
.btn-save { background: var(--primary); border: none; color: white; }
.btn-save:hover { background: var(--primary-hover); }
.btn-add { background: var(--primary); border: none; padding: 0.6rem 1.25rem; border-radius: var(--radius-sm); font-family: 'Cairo', sans-serif; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; color: white !important; cursor: pointer; }
.btn-add:hover { background: var(--primary-hover); color: white !important; }
</style>
@endsection

@section('content')
<div class="header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h1 class="page-title">إدارة المطاعم</h1>
        <p class="page-subtitle">عرض وإدارة المطاعم المسجلة</p>
    </div>
    <button class="btn-add" onclick="openModal()">
        <i class="fas fa-plus"></i>
        إضافة مطعم جديد
    </button>
</div>

<div class="filter-tabs mb-4">
    <input type="text" class="filter-tab" id="searchInput" placeholder="🔍 البحث عن مطعم..." onkeyup="searchByName(this.value)" style="min-width: 200px; text-align: right;">
    <select class="filter-tab" id="statusFilter" onchange="filterByStatus(this.value)" style="border:none;background:transparent; min-width:120px;">
        <option value="all">كل الحالات</option>
        <option value="1">نشط</option>
        <option value="0">غير نشط</option>
    </select>
    <select class="filter-tab" id="categoryFilter" onchange="filterByCategory(this.value)" style="border:none;background:transparent;">
        <option value="all">كل التصنيفات</option>
        <option value="مشروبات">مشروبات</option>
        <option value="حلويات">حلويات</option>
        <option value="شاورما">شاورما</option>
    </select>
</div>

<div class="data-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>المطعم</th>
                    <th>التقييم</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody id="restaurantTable">
                @foreach($restaurants as $restaurant)
                <tr data-status="{{ $restaurant->is_active ? 'active' : 'inactive' }}" data-category="{{ $restaurant->category }}" data-name="{{ strtolower($restaurant->name) }}">
                    <td>
                        <div class="restaurant-name">
                            @if($restaurant->image)
                            <img src="{{ asset('storage/' . $restaurant->image) }}" class="restaurant-icon" style="object-fit: cover;">
                            @else
                            <div class="restaurant-icon"><i class="fas fa-store"></i></div>
                            @endif
                            <div>
                                <div>{{ $restaurant->name }}</div>
                                <small style="color: var(--text-muted); font-size: 0.8rem;">{{ $restaurant->category }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="rating">
                            <span>4.5</span>
                            <i class="fas fa-star text-warning"></i>
                            <span class="rating-count">(260)</span>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-column gap-1">
                            <span class="badge {{ $restaurant->is_active ? 'badge-success' : 'badge-danger' }} status-badge" style="cursor: pointer;" onclick="toggleActive({{ $restaurant->id }})">
                                <i class="fas {{ $restaurant->is_active ? 'fa-check' : 'fa-times' }}"></i>
                                {{ $restaurant->is_active ? 'نشط' : 'غير نشط' }}
                            </span>
                            <span class="badge {{ $restaurant->is_open ? 'badge-outline-success' : 'badge-outline-danger' }} status-badge" style="cursor: pointer;" onclick="toggleOpenStatus({{ $restaurant->id }})">
                                <i class="fas {{ $restaurant->is_open ? 'fa-lock-open' : 'fa-lock' }}"></i>
                                {{ $restaurant->is_open ? 'مفتوح' : 'مغلق' }}
                            </span>
                        </div>
                    </td>
                    <td>
                        <button class="btn-action btn-edit" onclick="editRestaurant({{ $restaurant->id }}, '{{ $restaurant->name }}', '{{ $restaurant->category }}', '{{ $restaurant->email }}', '{{ $restaurant->phone }}', {{ $restaurant->is_active ? 'true' : 'false' }}, {{ $restaurant->is_open ? 'true' : 'false' }}, '{{ $restaurant->image ?? '' }}')">
                            <i class="fas fa-edit"></i>
                            تعديل
                        </button>
                        <form action="{{ route('admin.restaurants.destroy', $restaurant->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-action btn-delete" onclick="return confirm('هل أنت متأكد؟')">
                                <i class="fas fa-trash"></i>
                                حذف
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal" id="restaurantModal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="modal-title">
                <i class="fas fa-store"></i>
                <span id="modalTitle">إضافة مطعم جديد</span>
            </span>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form action="{{ route('admin.restaurants.store') }}" method="POST" id="restaurantForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-body">
                <div class="form-group text-center mb-3">
                    <div class="restaurant-avatar" id="avatarPreview" style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; overflow: hidden;" onclick="document.getElementById('restaurantImage').click()">
                        <i class="fas fa-camera" style="font-size: 1.5rem; color: var(--primary);"></i>
                    </div>
                    <input type="file" name="image" id="restaurantImage" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    <div class="mt-2"><small class="text-muted">صورة المطعم</small></div>
                </div>
                <div class="form-group">
                    <label class="form-label">اسم المطعم</label>
                    <input type="text" class="form-control" name="name" id="restaurantName" required>
                </div>
                <div class="form-group">
                    <label class="form-label">التصنيف</label>
                    <select class="form-control" name="category" id="restaurantCategory" required>
                        <option value="مشروبات">مشروبات</option>
                        <option value="حلويات">حلويات</option>
                        <option value="شاورما">شاورما</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" class="form-control" name="email" id="restaurantEmail" required>
                </div>
                <div class="form-group">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password" class="form-control" name="password" id="restaurantPassword">
                    <small class="text-muted">اتركها فارغة إذا لا تريد تغيير كلمة المرور</small>
                </div>
                <div class="form-group">
                    <label class="form-label">الهاتف</label>
                    <input type="text" class="form-control" name="phone" id="restaurantPhone">
                </div>
                <div class="form-group">
                    <label class="form-label">الحالة (نشط/غير نشط)</label>
                    <select class="form-control" name="is_active" id="restaurantStatus">
                        <option value="1">نشط</option>
                        <option value="0">غير نشط</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">حالة الفتح (مفتوح/مغلق)</label>
                    <select class="form-control" name="is_open" id="restaurantOpen">
                        <option value="1">مفتوح</option>
                        <option value="0">مغلق</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">إلغاء</button>
                <button type="submit" class="btn btn-save">حفظ</button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').innerHTML = '<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover;">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleActive(id) {
    fetch('/admin/restaurants/' + id + '/toggle-status', {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    }).then(() => window.location.reload());
}

function toggleOpenStatus(id) {
    fetch('/admin/restaurants/' + id + '/toggle-open', {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    }).then(() => window.location.reload());
}

function filterByStatus(status) {
    document.getElementById('statusFilter').value = status;
    applyFilters();
}

function filterByCategory(category) {
    document.getElementById('categoryFilter').value = category;
    applyFilters();
}

function searchByName(query) {
    document.getElementById('searchInput').value = query;
    applyFilters();
}

function applyFilters() {
    const rows = document.querySelectorAll('#restaurantTable tr');
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusValue = document.getElementById('statusFilter').value;
    const categoryValue = document.getElementById('categoryFilter').value;
    
    rows.forEach(row => {
        const rowStatus = row.dataset.status;
        const rowCategory = row.dataset.category;
        const rowName = row.dataset.name || '';
        
        let statusMatch = true;
        if (statusValue !== 'all') {
            const filterActive = statusValue == 1;
            statusMatch = (filterActive && rowStatus === 'active') || (!filterActive && rowStatus === 'inactive');
        }
        
        const categoryMatch = categoryValue === 'all' || rowCategory === categoryValue;
        const searchMatch = searchTerm === '' || rowName.includes(searchTerm);

        row.style.display = (statusMatch && categoryMatch && searchMatch) ? '' : 'none';
    });
}

function openModal() {
    document.getElementById('modalTitle').textContent = 'إضافة مطعم جديد';
    document.getElementById('restaurantForm').action = '{{ route("admin.restaurants.store") }}';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('restaurantForm').reset();
    document.getElementById('avatarPreview').innerHTML = '<i class="fas fa-camera" style="font-size: 1.5rem; color: var(--primary);"></i>';
    document.getElementById('restaurantModal').classList.add('show');
}

function editRestaurant(id, name, category, email, phone, isActive, isOpen, image = '') {
    document.getElementById('modalTitle').textContent = 'تعديل مطعم';
    document.getElementById('restaurantForm').action = '/admin/restaurants/' + id;
    document.getElementById('formMethod').value = 'PUT';
    
    document.getElementById('restaurantName').value = name;
    document.getElementById('restaurantCategory').value = category;
    document.getElementById('restaurantEmail').value = email;
    document.getElementById('restaurantPhone').value = phone || '';
    document.getElementById('restaurantStatus').value = isActive ? '1' : '0';
    document.getElementById('restaurantOpen').value = isOpen ? '1' : '0';
    document.getElementById('restaurantPassword').value = '';
    
    if (image) {
        document.getElementById('avatarPreview').innerHTML = '<img src="/storage/' + image + '" style="width: 100%; height: 100%; object-fit: cover;">';
    } else {
        document.getElementById('avatarPreview').innerHTML = '<i class="fas fa-camera" style="font-size: 1.5rem; color: var(--primary);"></i>';
    }
    
    document.getElementById('restaurantModal').classList.add('show');
}

function closeModal() {
    document.getElementById('restaurantModal').classList.remove('show');
}

document.getElementById('restaurantModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endsection