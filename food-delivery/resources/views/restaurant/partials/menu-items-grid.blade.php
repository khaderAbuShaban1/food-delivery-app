@if(count($menuItems ?? []) > 0)
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
    @foreach($menuItems as $item)
    <div class="col animate-fade-in" style="animation-delay: {{ $loop->index * 0.1 }}s;">
        <div class="menu-card glass-card h-100">
            <div style="height: 160px; overflow: hidden; position: relative; background: var(--bg-card);">
                @php
                    $image = $item->image ?? '';
                    $isHttp = strlen($image) > 4 && substr($image, 0, 4) === 'http';
                    $imageSrc = $isHttp ? $image : ($image ? asset('storage/' . $image) : null);
                @endphp
                @if($imageSrc)
                    <img src="{{ $imageSrc }}" alt="{{ $item->name }}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null;this.src='https://placehold.co/600x400/EEF2F7/94A3B8?text=No+Image'">
                @else
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <i class="bi bi-image" style="font-size: 2.5rem; color: var(--text-muted);"></i>
                    </div>
                @endif
                <div class="menu-item-status">
                    <button type="button"
                            class="status-chip {{ $item->is_available ? 'available' : 'unavailable' }} js-toggle-availability"
                            data-id="{{ $item->id }}"
                            data-open-text="متاح"
                            data-close-text="غير متاح">
                        {{ $item->is_available ? 'متاح' : 'غير متاح' }}
                    </button>
                </div>
                <span class="menu-price position-absolute" style="bottom: 10px; left: 10px; background: #FFFFFF; padding: 0.375rem 0.75rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                    @price($item->price)
                </span>
            </div>

            <div class="p-3">
                <span class="badge mb-2" style="background: rgba(249, 115, 22, 0.15); color: var(--accent-primary); font-size: 0.7rem;">
                    {{ $categories[$item->category] ?? $item->category ?? 'أخرى' }}
                </span>
                <h5 class="fw-semibold mb-1" style="color: var(--text-primary); font-size: 1rem;">{{ $item->name }}</h5>
                <p class="mb-3" style="color: var(--text-secondary); font-size: 0.8rem; line-height: 1.4; min-height: 2.8em; overflow: hidden;">
                    {{ $item->description ?? 'لا يوجد وصف' }}
                </p>

                <div class="d-flex gap-2 item-actions">
                    <button class="btn btn-sm btn-outline flex-grow-1 edit-btn"
                        data-id="{{ $item->id }}"
                        data-name="{{ $item->name }}"
                        data-price="{{ $item->price }}"
                        data-description="{{ $item->description ?? '' }}"
                        data-category="{{ $item->category ?? '' }}"
                        data-is-available="{{ $item->is_available ? '1' : '0' }}"
                        data-image="{{ $item->image ?? '' }}"
                        data-url="{{ $restaurant ? route('restaurant.menu.update', [$restaurant->id, $item->id]) : '#' }}">
                        <i class="bi bi-pencil me-1"></i>تعديل
                    </button>
                    <form action="{{ $restaurant ? route('restaurant.menu.destroy', [$restaurant->id, $item->id]) : '#' }}" method="POST" class="d-inline flex-grow-1 js-delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline w-100 text-danger">
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
    <h3 class="mt-4 mb-2" style="color: var(--text-primary);">لا توجد نتائج</h3>
    <p class="mb-4" style="color: var(--text-secondary);">جرب تعديل الفلاتر أو إعادة تعيينها</p>
    <button type="button" class="btn btn-orange js-reset-filters-trigger">
        <i class="bi bi-arrow-clockwise me-2"></i>إعادة تعيين الفلاتر
    </button>
</div>
@endif
