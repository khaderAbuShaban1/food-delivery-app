@extends('layouts.admin')

@section('title', 'إدارة الطلبات')

@section('styles')
<style>
.orders-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 1.5rem;
    align-items: start;
}

.order-detail-card {
    background: var(--white);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, var(--primary) 0%, #E85A1B 100%);
    color: white;
}

.detail-id {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.detail-id h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
}

.btn-copy {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 0.4rem 0.75rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.2s;
}

.btn-copy:hover {
    background: rgba(255,255,255,0.3);
}

.detail-body {
    padding: 1.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.info-card {
    background: #FAFAFA;
    border-radius: 12px;
    padding: 1rem;
    border: 1px solid var(--border);
}

.info-card h6 {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.info-card p {
    margin: 0;
    font-weight: 600;
    color: var(--text-dark);
}

.section-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--primary);
    display: inline-block;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
}

.items-table th {
    text-align: right;
    padding: 0.75rem;
    background: #FAFAFA;
    font-size: 0.8rem;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border);
}

.items-table td {
    padding: 0.75rem;
    border-bottom: 1px dashed var(--border);
    vertical-align: middle;
}

.item-img {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    object-fit: cover;
    background: var(--primary-muted);
    min-width: 40px;
}

.pricing-summary {
    background: #FAFAFA;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.payment-proof-empty {
    color: var(--text-muted);
    margin: 0;
}

.payment-method-card {
    display: flex;
    gap: 1rem;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1rem;
    margin-top: 0.75rem;
    align-items: flex-start;
}

.payment-method-logo {
    width: 68px;
    height: 68px;
    border-radius: 12px;
    object-fit: cover;
    background: #f3f4f6;
    border: 1px solid var(--border);
    flex-shrink: 0;
}

.payment-method-details {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 0;
}

.payment-method-row {
    font-size: 0.88rem;
    color: var(--text-dark);
}

.payment-method-row strong {
    color: var(--text-muted);
    margin-left: 0.35rem;
}

.customer-name-highlight {
    font-weight: 700;
    color: var(--primary);
}

.customer-name-label {
    color: var(--text-muted);
    font-weight: 600;
}

.customer-info-stack {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-wrap: wrap;
}

.customer-id-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    width: fit-content;
    background: var(--primary-muted);
    color: var(--primary);
    border: 1px solid rgba(255, 107, 44, 0.25);
    padding: 0.3rem 0.65rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
}

.pricing-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.9rem;
}

.pricing-row.discount {
    color: #059669;
}

.pricing-row.total {
    border-top: 2px solid var(--primary);
    margin-top: 0.5rem;
    padding-top: 1rem;
    font-weight: 700;
    font-size: 1.1rem;
}

.pricing-row.total span:last-child {
    color: var(--primary);
    font-size: 1.25rem;
}

.timeline {
    position: relative;
    padding-right: 2rem;
    margin-bottom: 1.5rem;
}

.timeline::before {
    content: '';
    position: absolute;
    right: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--border);
}

.timeline-step {
    position: relative;
    padding: 1rem;
    padding-right: 1.5rem;
    margin-bottom: 0.5rem;
    background: #FAFAFA;
    border-radius: 12px;
    transition: all 0.3s;
}

.timeline-step::before {
    content: '';
    position: absolute;
    right: -2rem;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--border);
    border: 3px solid white;
    box-shadow: 0 0 0 2px var(--border);
}

.timeline-step.active::before {
    background: var(--primary);
    box-shadow: 0 0 0 2px var(--primary);
}

.timeline-step.completed::before {
    background: #059669;
    box-shadow: 0 0 0 2px #059669;
}

.timeline-step.active {
    background: var(--primary-muted);
}

.timeline-step h6 {
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.timeline-step p {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin: 0;
}

.timeline-step.active h6 {
    color: var(--primary);
}

.action-buttons {
    display: flex;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border);
}

.btn-action {
    flex: 1;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    font-family: 'Cairo', sans-serif;
}

.btn-accept {
    background: var(--primary);
    color: white;
}

.btn-accept:hover {
    background: #E85A1B;
}

.btn-cancel {
    background: #FEE2E2;
    color: #DC2626;
}

.btn-cancel:hover {
    background: #FECACA;
}

.orders-list-card {
    background: var(--white);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.list-header {
    padding: 1rem 1.25rem;
    background: #FAFAFA;
    border-bottom: 1px solid var(--border);
}

.list-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
}

.orders-list {
    max-height: calc(100vh - 280px);
    overflow-y: auto;
}

.orders-list-card .pagination-nav {
    display: flex;
    justify-content: center;
}

.orders-list-card .pagination {
    --bs-pagination-color: var(--primary);
    --bs-pagination-hover-color: var(--primary);
    --bs-pagination-focus-color: var(--primary);
    --bs-pagination-bg: #fff;
    --bs-pagination-hover-bg: var(--primary-muted);
    --bs-pagination-focus-bg: var(--primary-muted);
    --bs-pagination-border-color: var(--border);
    --bs-pagination-hover-border-color: var(--primary);
    --bs-pagination-focus-box-shadow: 0 0 0 3px var(--primary-muted);
    --bs-pagination-active-color: #fff;
    --bs-pagination-active-bg: var(--primary);
    --bs-pagination-active-border-color: var(--primary);
    --bs-pagination-disabled-color: var(--text-muted);
    --bs-pagination-disabled-bg: #f9fafb;
    --bs-pagination-disabled-border-color: var(--border);
    gap: 0.35rem;
    margin-bottom: 0;
}

.orders-list-card .page-link {
    border-radius: 10px !important;
    min-width: 2.3rem;
    text-align: center;
    font-weight: 600;
    transition: all 0.2s ease;
}

.orders-list-card .page-item.active .page-link {
    box-shadow: 0 6px 16px rgba(255, 107, 44, 0.25);
}

.order-list-item {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: all 0.2s;
}

.order-list-item:hover {
    background: #FAFAFA;
}

.order-list-item.selected {
    background: var(--primary-muted);
    border-right: 3px solid var(--primary);
}

.list-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.list-item-id {
    font-weight: 700;
    font-size: 0.85rem;
}

.list-item-price {
    font-weight: 700;
    color: var(--primary);
}

.list-item-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.badge-status {
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}

.badge-status.pending_payment_verification { background: #FEF3C7; color: #B45309; }
.badge-status.payment_verified { background: #DBEAFE; color: #2563EB; }
.badge-status.payment_rejected { background: #FEE2E2; color: #DC2626; }
.badge-status.accepted_by_restaurant { background: #E0F2FE; color: #0369A1; }
.badge-status.preparing { background: #EDE9FE; color: #7C3AED; }
.badge-status.on_the_way { background: #CFFAFE; color: #0891B2; }
.badge-status.delivered { background: #D1FAE5; color: #059669; }

.list-item-time {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.empty-details {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
}

.empty-details i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.filter-bar {
    background: var(--white);
    border-radius: 20px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.filter-bar-top {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1rem;
}

.status-tabs {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
    flex: 1;
    min-width: 0;
}

.status-tab {
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text-muted);
    background: transparent;
    border: 1px solid var(--border);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    font-family: 'Cairo', sans-serif;
}

.status-tab:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.status-tab.active {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
}

.filter-bar-bottom {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-group label {
    font-size: 0.8rem;
    color: var(--text-muted);
    white-space: nowrap;
}

.filter-input {
    padding: 0.5rem 1rem;
    border: 1px solid var(--border);
    border-radius: 25px;
    font-size: 0.85rem;
    outline: none;
    transition: all 0.2s;
    font-family: 'Cairo', sans-serif;
}

.filter-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-muted);
}

.filter-input::placeholder {
    color: var(--text-muted);
}

.input-icon-wrapper {
    position: relative;
}

.input-icon-wrapper i {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 0.85rem;
}

.input-icon-wrapper .filter-input {
    padding-right: 2.5rem;
}

.filter-select {
    padding: 0.5rem 2rem 0.5rem 1rem;
    border: 1px solid var(--border);
    border-radius: 25px;
    font-size: 0.85rem;
    outline: none;
    background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236B7280' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E") no-repeat left 1rem center;
    appearance: none;
    cursor: pointer;
    font-family: 'Cairo', sans-serif;
    min-width: 150px;
}

.filter-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-muted);
}

.search-input {
    min-width: 200px;
}

.date-input {
    width: 145px;
}

.orders-layout {
    grid-template-columns: 1fr 380px;
    gap: 1.5rem;
    align-items: start;
}

@media (max-width: 1200px) {
    .orders-layout {
        grid-template-columns: 1fr;
    }

    .orders-list-card {
        order: -1;
    }

    .orders-list {
        max-height: 300px;
    }
}

@media (max-width: 992px) {
    .filter-bar-top {
        flex-direction: column;
        align-items: stretch;
    }

    .status-tabs {
        justify-content: flex-start;
    }

    .filter-bar-bottom {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-group {
        width: 100%;
    }

    .filter-select,
    .search-input,
    .date-input {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }

    .detail-body {
        padding: 1rem;
    }

    .status-tabs {
        flex-wrap: nowrap;
        justify-content: flex-start;
    }
}

.filter-tab {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    color: var(--text-muted);
    font-weight: 500;
    cursor: pointer;
    border: none;
    background: transparent;
    font-family: 'Cairo', sans-serif;
    transition: all 0.2s;
}

.filter-tab:hover, .filter-tab.active {
    background: var(--primary);
    color: white;
}

@media (max-width: 1024px) {
    .orders-layout {
        grid-template-columns: 1fr;
    }

    .orders-list-card {
        order: -1;
    }

    .orders-list {
        max-height: 300px;
    }
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }

    .detail-body {
        padding: 1rem;
    }
}
</style>
@endsection

@section('content')
<div class="header">
    <h1 class="page-title">إدارة الطلبات</h1>
    <p class="page-subtitle">عرض ومتابعة جميع الطلبات</p>
</div>

<div class="filter-bar">
    <div class="filter-bar-top">
        <div class="filter-group">
            <label>من:</label>
            <input type="date" class="filter-input date-input" id="dateFrom" placeholder="من تاريخ">
        </div>
        <div class="filter-group">
            <label>إلى:</label>
            <input type="date" class="filter-input date-input" id="dateTo" placeholder="إلى تاريخ">
        </div>
        <div class="filter-group">
            <select class="filter-select" id="restaurantFilter">
                <option value="">جميع المطاعم</option>
                @foreach(App\Models\Restaurant::all() as $restaurant)
                <option value="{{ $restaurant->id }}">{{ $restaurant->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <div class="input-icon-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" class="filter-input search-input" id="searchInput" placeholder="البحث برقم الطلب...">
            </div>
        </div>
    </div>
    <div class="filter-bar-bottom">
        <div class="status-tabs">
            <button class="status-tab active" data-status="">الكل</button>
            <button class="status-tab" data-status="pending_payment_verification">انتظار التحقق</button>
            <button class="status-tab" data-status="payment_verified">دفع موثّق</button>
            <button class="status-tab" data-status="payment_rejected">رفض الدفع</button>
            <button class="status-tab" data-status="accepted_by_restaurant">مقبول المطعم</button>
            <button class="status-tab" data-status="preparing">تحضير</button>
            <button class="status-tab" data-status="on_the_way">بالطريق</button>
            <button class="status-tab" data-status="delivered">تم التسليم</button>
        </div>
    </div>
</div>

<div class="orders-layout">
    <div class="order-detail-card" id="orderDetails">
        <div class="empty-details">
            <i class="fas fa-shopping-bag"></i>
            <h4>اختر طلب</h4>
            <p>اختر طلب من القائمة لعرض التفاصيل</p>
        </div>
    </div>

    <div class="orders-list-card">
        <div class="list-header">
            <h3><i class="fas fa-list me-2"></i> الطلبات (<span id="orderCount">{{ $orders->total() }}</span>)</h3>
        </div>
        <div class="orders-list">
            @forelse($orders as $order)
            <div class="order-list-item" onclick="selectOrder({{ $order->id }})" data-order-id="{{ $order->id }}">
                <div class="list-item-header">
                    <span class="list-item-id">{{ $order->order_number }}</span>
                    <span class="list-item-price">{{ number_format($order->total_price, 2) }} شيكل</span>
                </div>
                <div class="list-item-footer">
                    <span class="badge-status {{ $order->status }}">{{ $statuses[$order->status] ?? $order->status }}</span>
                    <span class="list-item-time">{{ $order->created_at->diffForHumans() }}</span>
                </div>
            </div>
            @empty
            <div class="text-center py-4 text-muted">
                <p>لا توجد طلبات</p>
            </div>
            @endforelse
        </div>
        @if($orders->hasPages())
        <div class="p-2">
            {!! $orders->links('vendor.pagination.bootstrap-5') !!}
        </div>
        @endif
    </div>
</div>

<div class="text-center mt-4 text-muted" style="font-size: 0.8rem;">
    <p>0599887766 © International Software</p>
</div>
@endsection

@section('scripts')
<script>
const orderStatuses = @json($statuses);

function selectOrder(orderId) {
    $('.order-list-item').removeClass('selected');
    $(`.order-list-item[data-order-id="${orderId}"]`).addClass('selected');

    $.get(`/admin/orders/${orderId}/data`, function(order) {
        renderOrderDetails(order);
    });
}

function renderOrderDetails(order) {
    const timelineSteps = [
        { status: 'pending_payment_verification', title: 'انتظار التحقق', desc: 'بانتظار مراجعة إثبات الدفع' },
        { status: 'payment_verified', title: 'دفع موثّق', desc: 'اعتمد الطلب لمطعمه' },
        { status: 'accepted_by_restaurant', title: 'قبول المطعم', desc: 'وافق المطعم على تنفيذ الطلب' },
        { status: 'preparing', title: 'قيد التجهيز', desc: 'المطبخ يحضّر الطلب' },
        { status: 'on_the_way', title: 'بالطريق', desc: 'السائق في الطريق' },
        { status: 'delivered', title: 'تم التسليم', desc: 'تم توصيل الطلب' }
    ];

    const statusOrder = ['pending_payment_verification', 'payment_verified', 'accepted_by_restaurant', 'preparing', 'on_the_way', 'delivered'];
    const currentIndex = statusOrder.indexOf(order.status);

    let timelineHTML = timelineSteps.map(step => {
        const stepIndex = statusOrder.indexOf(step.status);
        let stepClass = '';
        if (order.status === 'payment_rejected') {
            stepClass = 'cancelled';
        } else if (stepIndex < currentIndex) {
            stepClass = 'completed';
        } else if (stepIndex === currentIndex) {
            stepClass = 'active';
        }
        return `
            <div class="timeline-step ${stepClass}">
                <h6>${step.title}</h6>
                <p>${step.desc}</p>
            </div>
        `;
    }).join('');

    let itemsHTML = order.order_items && order.order_items.length > 0
        ? order.order_items.map(item => {
            let image = item.image || '';
            let name = item.name || '';
            let displaySrc = 'https://placehold.co/50x50/FFE8DC/FF6B2C?text=Food';
            
            if (image && (image.startsWith('http') || image.startsWith('https'))) {
                displaySrc = image;
            } else if (image && image.startsWith('menu-images/')) {
                displaySrc = '/storage/' + image;
            }
            
            return `
            <tr>
                <td>
                    <img src="${displaySrc}" class="item-img" alt="${name}" onerror="this.onerror=null;this.src='https://placehold.co/50x50/FFE8DC/FF6B2C?text=Food'">
                </td>
                <td>${name || 'غير معروف'}</td>
                <td>×${item.quantity}</td>
                <td>${parseFloat(item.price).toFixed(2)} شيكل</td>
                <td>${(parseFloat(item.price) * item.quantity).toFixed(2)} شيكل</td>
            </tr>
        `;
        }).join('')
        : '<tr><td colspan="5" class="text-center text-muted">لا توجد أصناف</td></tr>';

    const paymentMethod = order.payment_method_details;
    const paymentMethodName = (paymentMethod && paymentMethod.bank_or_wallet_name) ? paymentMethod.bank_or_wallet_name : 'غير محدد';
    const paymentAccountName = (paymentMethod && paymentMethod.account_name) ? paymentMethod.account_name : 'غير محدد';
    const paymentTypeIsWallet = paymentMethod && paymentMethod.type === 'wallet';
    const paymentNumberLabel = paymentTypeIsWallet ? 'الهاتف' : 'رقم الحساب';
    const paymentNumberValue = paymentTypeIsWallet
        ? ((paymentMethod && paymentMethod.phone_number) || 'غير محدد')
        : ((paymentMethod && paymentMethod.account_number) || (paymentMethod && paymentMethod.phone_number) || 'غير محدد');

    const paymentMethodCard = paymentMethod ? `
        <div class="payment-method-card">
            <img
                src="${paymentMethod.image || 'https://placehold.co/80x80/FFE8DC/FF6B2C?text=Pay'}"
                class="payment-method-logo"
                alt="${paymentMethodName}"
                onerror="this.onerror=null;this.src='https://placehold.co/80x80/FFE8DC/FF6B2C?text=Pay'">
            <div class="payment-method-details">
                <div class="payment-method-row"><strong>${paymentTypeIsWallet ? 'المحفظة' : 'البنك'}:</strong>${paymentMethodName}</div>
                <div class="payment-method-row"><strong>اسم الحساب:</strong>${paymentAccountName}</div>
                <div class="payment-method-row"><strong>${paymentNumberLabel}:</strong>${paymentNumberValue}</div>
            </div>
        </div>
    ` : `
        <div class="payment-method-card">
            <img
                src="https://placehold.co/80x80/F3F4F6/9CA3AF?text=?"
                class="payment-method-logo"
                alt="غير محدد">
            <div class="payment-method-details">
                <div class="payment-method-row"><strong>طريقة الدفع:</strong>غير محدد</div>
                <div class="payment-method-row"><strong>اسم الحساب:</strong>غير محدد</div>
                <div class="payment-method-row"><strong>الهاتف / رقم الحساب:</strong>غير محدد</div>
            </div>
        </div>
    `;

    const proofBlock = `
        <h4 class="section-title"><i class="fas fa-receipt me-2"></i> إثبات الدفع</h4>
        <div class="pricing-summary mb-4">
            ${order.payment_proof_url
                ? `<p class="mb-2"><a href="${order.payment_proof_url}" target="_blank" rel="noopener">عرض الصورة الكاملة</a></p>
                   <a href="${order.payment_proof_url}" target="_blank" rel="noopener"><img src="${order.payment_proof_url}" alt="" style="max-height:220px;border-radius:12px;border:1px solid var(--border);"></a>`
                : '<p class="payment-proof-empty">لا يوجد إثبات دفع</p>'}
            ${order.payment_reference ? `<p class="mt-2 mb-0"><strong>رقم المرجع:</strong> ${order.payment_reference}</p>` : ''}
            ${paymentMethodCard}
        </div>
    `;

    const html = `
        <div class="detail-header">
            <div class="detail-id">
                <h3>${order.order_number || 'ORD-' + order.id}</h3>
                <span class="badge-status ${order.status}" style="background: rgba(255,255,255,0.2); color: white;">${orderStatuses[order.status] || order.status}</span>
            </div>
            <button class="btn-copy" onclick="copyOrderId(${order.id})">
                <i class="fas fa-copy"></i> نسخ
            </button>
        </div>
        <div class="detail-body">
            ${proofBlock}
            <div class="info-grid">
                <div class="info-card">
                    <h6><i class="fas fa-user me-1"></i> معلومات العميل</h6>
                    <div class="customer-info-stack">
                        <span class="customer-id-chip"><i class="fas fa-id-badge"></i> ID: ${(order.customer && order.customer.id) ? order.customer.id : (order.customer_id || 'غير محدد')}</span>
                        <p class="mb-0"><span class="customer-name-label">اسم الزبون:</span> <span class="customer-name-highlight">${(order.customer && order.customer.name) ? order.customer.name : 'غير محدد'}</span></p>
                    </div>
                </div>
                <div class="info-card">
                    <h6><i class="fas fa-store me-1"></i> المطعم</h6>
                    <p>${order.restaurant ? order.restaurant.name : 'غير معروف'}</p>
                </div>
                <div class="info-card">
                    <h6><i class="fas fa-calendar me-1"></i> تاريخ الطلب</h6>
                    <p>${new Date(order.created_at).toLocaleDateString('ar-SA')}</p>
                </div>
                <div class="info-card">
                    <h6><i class="fas fa-clock me-1"></i> الوقت</h6>
                    <p>${new Date(order.created_at).toLocaleTimeString('ar-SA')}</p>
                </div>
            </div>

            <h4 class="section-title"><i class="fas fa-shopping-bag me-2"></i> الأصناف</h4>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"></th>
                        <th>الاسم</th>
                        <th>الكمية</th>
                        <th>السعر</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>${itemsHTML}</tbody>
            </table>

            <h4 class="section-title"><i class="fas fa-calculator me-2"></i> ملخص السعر</h4>
            <div class="pricing-summary">
                <div class="pricing-row">
                    <span>المجموع الفرعي</span>
                    <span>${parseFloat(order.total_price).toFixed(2)} شيكل</span>
                </div>
                <div class="pricing-row discount">
                    <span>الخصم</span>
                    <span>- 0.00 شيكل</span>
                </div>
                <div class="pricing-row">
                    <span>رسوم التوصيل</span>
                    <span>10.00 شيكل</span>
                </div>
                <div class="pricing-row total">
                    <span>الإجمالي</span>
                    <span>${(parseFloat(order.total_price) + 10).toFixed(2)} شيكل</span>
                </div>
            </div>

            <h4 class="section-title"><i class="fas fa-history me-2"></i> تتبع الطلب</h4>
            <div class="timeline">${timelineHTML}</div>

            ${order.status === 'pending_payment_verification' ? `
            <div class="action-buttons">
                <button onclick="verifyPayment(${order.id})" class="btn-action btn-accept">
                    <i class="fas fa-check me-1"></i> التحقق من الدفع
                </button>
                <button onclick="rejectPayment(${order.id})" class="btn-action btn-cancel">
                    <i class="fas fa-times me-1"></i> رفض الدفع
                </button>
            </div>
            ` : ''}
        </div>
    `;

    $('#orderDetails').html(html);
}

function verifyPayment(orderId) {
    if (!confirm('تأكيد أن الدفع صحيح وإرسال الطلب إلى المطعم؟')) return;
    $.ajax({
        url: `/admin/orders/${orderId}/verify-payment`,
        type: 'PATCH',
        data: { _token: '{{ csrf_token() }}' },
        success: function() {
            toastr.success('تم التحقق من الدفع');
            loadOrders();
            selectOrder(orderId);
        },
        error: function() {
            toastr.error('حدث خطأ');
        }
    });
}

function rejectPayment(orderId) {
    if (!confirm('رفض إثبات الدفع؟ لن يصل الطلب إلى المطعم.')) return;
    $.ajax({
        url: `/admin/orders/${orderId}/reject-payment`,
        type: 'PATCH',
        data: { _token: '{{ csrf_token() }}' },
        success: function() {
            toastr.success('تم رفض الدفع');
            loadOrders();
            selectOrder(orderId);
        },
        error: function() {
            toastr.error('حدث خطأ');
        }
    });
}

function copyOrderId(id) {
    var orderNum = $('#orderDetails .detail-id h3').text();
    navigator.clipboard.writeText(orderNum.trim()).then(function() {
        toastr.success('تم نسخ رقم الطلب');
    });
}

let currentFilters = {
    status: '',
    date_from: '',
    date_to: '',
    restaurant_id: '',
    search: ''
};

function loadOrders() {
    const selectedOrderId = $('.order-list-item.selected').data('order-id');
    $.ajax({
        url: '/admin/orders/list',
        type: 'GET',
        data: currentFilters,
        success: function(response) {
            renderOrdersList(response.orders);
            $('#orderCount').text(response.total);
            if (selectedOrderId) {
                const exists = $(`.order-list-item[data-order-id="${selectedOrderId}"]`).length > 0;
                if (exists) {
                    selectOrder(selectedOrderId);
                }
            }
        }
    });
}

function renderOrdersList(orders) {
    if (orders.length === 0) {
        $('.orders-list').html('<div class="text-center py-4 text-muted"><p>لا توجد طلبات</p></div>');
        return;
    }

    let html = orders.map(order => `
        <div class="order-list-item" onclick="selectOrder(${order.id})" data-order-id="${order.id}">
            <div class="list-item-header">
                <span class="list-item-id">${order.order_number}</span>
                <span class="list-item-price">${parseFloat(order.total_price).toFixed(2)} شيكل</span>
            </div>
            <div class="list-item-footer">
                <span class="badge-status ${order.status}">${orderStatuses[order.status] || order.status}</span>
                <span class="list-item-time">${formatDate(order.created_at)}</span>
            </div>
        </div>
    `).join('');

    $('.orders-list').html(html);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'الآن';
    if (minutes < 60) return 'منذ ' + minutes + ' دقيقة';
    if (hours < 24) return 'منذ ' + hours + ' ساعة';
    if (days < 7) return 'منذ ' + days + ' يوم';
    return date.toLocaleDateString('ar-SA');
}

$('.status-tab').on('click', function() {
    $('.status-tab').removeClass('active');
    $(this).addClass('active');
    currentFilters.status = $(this).data('status');
    loadOrders();
});

$('#dateFrom, #dateTo').on('change', function() {
    currentFilters.date_from = $('#dateFrom').val();
    currentFilters.date_to = $('#dateTo').val();
    loadOrders();
});

$('#restaurantFilter').on('change', function() {
    currentFilters.restaurant_id = $(this).val();
    loadOrders();
});

let searchTimeout;
$('#searchInput').on('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        currentFilters.search = $('#searchInput').val();
        loadOrders();
    }, 300);
});

@if($orders->count() > 0)
selectOrder({{ $orders->first()->id }});
@endif

setInterval(loadOrders, 5000);
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) loadOrders();
});
</script>
@endsection