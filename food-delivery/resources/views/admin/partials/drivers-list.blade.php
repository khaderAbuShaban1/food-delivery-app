<div class="stats-row">
    <div class="stat-box stat-pending">
        <span>بانتظار الموافقة</span>
        <strong>{{ $stats['pending'] }}</strong>
    </div>
    <div class="stat-box stat-approved">
        <span>موافق عليهم</span>
        <strong>{{ $stats['approved'] }}</strong>
    </div>
    <div class="stat-box stat-rejected">
        <span>مرفوضين</span>
        <strong>{{ $stats['rejected'] }}</strong>
    </div>
</div>

<div class="filters" aria-label="فلترة حالة السائقين">
    @foreach($statusLabels as $key => $label)
        <a class="filter-pill {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.drivers.index', ['status' => $key]) }}">
            <span class="filter-dot {{ $key }}"></span>
            <span>{{ $label }}</span>
            <strong>{{ $stats[$key] ?? 0 }}</strong>
        </a>
    @endforeach
</div>

<div class="drivers-card">
    <div class="drivers-card-head">
        <div>
            <h2>قائمة السائقين</h2>
            <p>الطلبات الأحدث تظهر أولاً، ويتم تحديث القائمة تلقائياً.</p>
        </div>
        <span class="refresh-note" data-refresh-state>تحديث تلقائي</span>
    </div>

    <div class="drivers-table-wrap">
        <table class="drivers-table">
            <thead>
                <tr>
                    <th>السائق</th>
                    <th>رقم الهوية</th>
                    <th>التواصل</th>
                    <th>نوع المركبة</th>
                    <th>تاريخ التسجيل</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drivers as $driver)
                    @php
                        $driverStatus = $driver->approval_status ?? 'pending';
                        $profileImage = $driver->profile_image ? asset('storage/' . $driver->profile_image) : null;
                    @endphp
                    <tr data-driver-row="{{ $driver->id }}">
                        <td>
                            <div class="driver-profile">
                                @if($profileImage)
                                    <img class="driver-avatar" src="{{ $profileImage }}" alt="{{ $driver->name }}">
                                @else
                                    <div class="driver-avatar avatar-fallback">{{ mb_substr((string) $driver->name, 0, 1) ?: '?' }}</div>
                                @endif
                                <div class="driver-copy">
                                    <strong>{{ $driver->name }}</strong>
                                    <span>{{ $driver->email ?: '-' }}</span>
                                    <div class="doc-links">
                                        @if($driver->profile_image)
                                            <a class="doc-link" href="{{ asset('storage/' . $driver->profile_image) }}" target="_blank" rel="noopener">الصورة</a>
                                        @endif
                                        @if($driver->national_id_image)
                                            <a class="doc-link" href="{{ asset('storage/' . $driver->national_id_image) }}" target="_blank" rel="noopener">الهوية</a>
                                        @endif
                                        @if($driver->vehicle_image)
                                            <a class="doc-link" href="{{ asset('storage/' . $driver->vehicle_image) }}" target="_blank" rel="noopener">المركبة</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="cell-label">رقم الهوية</span>
                            <strong class="mono-value">{{ $driver->national_id ?: '-' }}</strong>
                        </td>
                        <td>
                            <span class="cell-label">الهاتف</span>
                            <div>{{ $driver->phone ?: '-' }}</div>
                            <span class="cell-muted">{{ $driver->city ?: 'بدون مدينة' }}</span>
                        </td>
                        <td>
                            <span class="cell-label">المركبة</span>
                            <div>{{ $vehicleLabels[$driver->vehicle_type] ?? str_replace('_', ' ', (string) $driver->vehicle_type) ?: '-' }}</div>
                            @if($driver->vehicle_plate_number)
                                <span class="cell-muted">لوحة: {{ $driver->vehicle_plate_number }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="cell-label">تاريخ التسجيل</span>
                            <div>{{ optional($driver->created_at)->format('Y-m-d') }}</div>
                            <span class="cell-muted">{{ optional($driver->created_at)->format('H:i') }}</span>
                        </td>
                        <td>
                            <span class="status-badge {{ $driverStatus }}">
                                {{ $statusLabels[$driverStatus] ?? $driverStatus }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <button
                                    class="action-btn approve"
                                    type="button"
                                    @if($driverStatus === 'pending')
                                        data-driver-action="approve"
                                        data-driver-id="{{ $driver->id }}"
                                        data-driver-name="{{ $driver->name }}"
                                        data-action-url="{{ route('admin.drivers.approve', $driver->id) }}"
                                    @else
                                        disabled
                                    @endif
                                >
                                    موافقة
                                </button>
                                <button
                                    class="action-btn reject"
                                    type="button"
                                    @if($driverStatus === 'pending')
                                        data-driver-action="reject"
                                        data-driver-id="{{ $driver->id }}"
                                        data-driver-name="{{ $driver->name }}"
                                        data-action-url="{{ route('admin.drivers.reject', $driver->id) }}"
                                    @else
                                        disabled
                                    @endif
                                >
                                    رفض
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            لا توجد طلبات ضمن هذا الفلتر.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($drivers->hasPages())
        <div class="pagination-wrap">
            {{ $drivers->links() }}
        </div>
    @endif
</div>
