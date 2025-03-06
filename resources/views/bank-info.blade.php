<style>
    .sepay-container {
        max-width: 700px;
        margin: 2rem 0;
    }

    .sepay-card {
        background-color: var(--bs-body-bg, #fff);
        border-radius: 12px;
        border: 1px solid var(--bs-primary);
        padding: 24px;
        margin-bottom: 24px;
        transition: all 0.3s ease;
    }

    .sepay-heading {
        font-size: 18px;
        font-weight: 600;
        color: var(--bs-heading-color, #333);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sepay-heading svg {
        width: 22px;
        height: 22px;
        color: var(--primary-color, #0d6efd);
    }

    .sepay-tabs {
        display: flex;
        border-bottom: 1px solid var(--bs-border-color, #dee2e6);
        margin-bottom: 24px;
    }

    .sepay-tab {
        padding: 12px 20px;
        cursor: pointer;
        font-weight: 500;
        color: var(--bs-secondary-color, #6c757d);
        border-bottom: 2px solid transparent;
        transition: all 0.2s ease;
    }

    .sepay-tab.active {
        color: var(--primary-color, #0d6efd);
        border-bottom-color: var(--primary-color, #0d6efd);
    }

    .sepay-qr-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 24px;
    }

    .sepay-qr-code {
        width: 300px;
        height: 300px;
        padding: 12px;
        border-radius: 12px;
    }

    .sepay-qr-caption {
        font-size: 14px;
        color: var(--bs-secondary-color, #6c757d);
        text-align: center;
    }

    .sepay-detail-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
    }

    .sepay-detail-row:last-child {
        border-bottom: none;
    }

    .sepay-detail-label {
        color: var(--bs-secondary-color, #6c757d);
        font-size: 14px;
    }

    .sepay-detail-value {
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sepay-warning {
        background-color: rgba(255, 243, 205, 0.5);
        border-left: 4px solid #ffc107;
        border-radius: 6px;
        padding: 16px;
        margin-top: 24px;
        font-size: 14px;
        line-height: 1.6;
    }

    .sepay-warning strong {
        color: #dc3545;
    }

    .sepay-copy-btn {
        background: transparent;
        border: none;
        cursor: pointer;
        border-radius: 6px;
        color: var(--bs-secondary-color, #6c757d);
        transition: all 0.2s;
    }

    .sepay-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 16px;
        background-color: var(--bs-tertiary-bg, #f8f9fa);
        border-radius: 8px;
        margin-top: 24px;
        font-weight: 500;
    }

    .sepay-success {
        text-align: center;
        padding: 40px 20px;
        background-color: var(--bs-tertiary-bg, #f8f9fa);
        border-radius: 12px;
        animation: fadeIn 0.5s ease;
    }

    .sepay-success svg {
        width: 64px;
        height: 64px;
        color: #198754;
        margin-bottom: 16px;
    }

    .sepay-success h4 {
        font-size: 24px;
        color: #198754;
        margin-bottom: 0;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 576px) {
        .sepay-card {
            padding: 16px;
            border-radius: 8px;
        }

        .sepay-tabs {
            overflow-x: auto;
            white-space: nowrap;
        }

        .sepay-tab {
            padding: 12px 16px;
        }
    }
</style>

<div id="fob-sepay-bank" class="sepay-container">
    @if ($payment->status != \Botble\Payment\Enums\PaymentStatusEnum::COMPLETED)
        <div id="sepay-bank-info">
            <div class="sepay-card">
                <div class="sepay-heading">
                    <x-core::icon name="ti ti-credit-card" />
                    Thanh toán qua chuyển khoản ngân hàng
                </div>

                <div class="sepay-tabs">
                    <div class="sepay-tab active">Quét QR</div>
                    <div class="sepay-tab">Thông tin chuyển khoản</div>
                </div>

                <div class="sepay-qr-container">
                    <div class="sepay-qr-code">
                        <img src="{{ $imageUrl }}" alt="QR Code" width="100%" height="auto">
                    </div>
                    <div class="sepay-qr-caption">Quét mã QR bằng ứng dụng ngân hàng hoặc ví điện tử</div>
                </div>

                <div class="sepay-details">
                    <div class="sepay-detail-row">
                        <div class="sepay-detail-label">Tên Ngân Hàng</div>
                        <div class="sepay-detail-value">{{ $bank }}</div>
                    </div>
                    <div class="sepay-detail-row">
                        <div class="sepay-detail-label">Chủ Tài Khoản</div>
                        <div class="sepay-detail-value">{{ $bankAccountHolder }}</div>
                    </div>
                    <div class="sepay-detail-row">
                        <div class="sepay-detail-label">Số Tài Khoản</div>
                        <div class="sepay-detail-value">
                            {{ $bankAccountNumber }}
                            <button class="sepay-copy-btn" data-clipboard="{{ $bankAccountNumber }}" data-bb-toggle="copy">
                                <x-core::icon name="ti ti-clipboard" />
                            </button>
                        </div>
                    </div>
                    <div class="sepay-detail-row">
                        <div class="sepay-detail-label">Nội Dung Chuyển Khoản</div>
                        <div class="sepay-detail-value">
                            {{ $chargeId }}
                            <button class="sepay-copy-btn" data-clipboard="{{ $chargeId }}" data-bb-toggle="copy">
                                <x-core::icon name="ti ti-clipboard" />
                            </button>
                        </div>
                    </div>
                    <div class="sepay-detail-row">
                        <div class="sepay-detail-label">Số Tiền Giao Dịch</div>
                        <div class="sepay-detail-value">
                            {{ $formattedOrderAmount = number_format($orderAmount, 0, ',', '.') . ' ₫' }}
                            <button class="sepay-copy-btn" data-clipboard="{{ $orderAmount }}" data-bb-toggle="copy">
                                <x-core::icon name="ti ti-clipboard" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="sepay-warning">
                    <p>Vui lòng giữ nguyên nội dung chuyển khoản <strong>{{ $chargeId }}</strong> và nhập đúng số tiền <strong>{{ $formattedOrderAmount }}</strong> để được xác nhận thanh toán tự động.</p>
                </div>

                <div class="sepay-loading" data-bb-toggle="sepay-transaction-status" data-url="{{ route('sepay.transactions.check') }}" data-charge-id="{{ $chargeId }}">
                    <span>Đang chờ thanh toán</span>
                    <img src="{{ url('vendor/core/plugins/fob-sepay/images/loading.gif') }}" width="20" height="20" alt="Loading">
                </div>
            </div>
        </div>
    @endif

    <div @style(['display: none' => $payment->status != \Botble\Payment\Enums\PaymentStatusEnum::COMPLETED]) id="sepay-transaction-status-done">
        <div class="sepay-card sepay-success">
            <x-core::icon name="ti ti-circle-check" />
            <h4>Thanh toán thành công</h4>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.sepay-tab');
        const qrContainer = document.querySelector('.sepay-qr-container');
        const detailsContainer = document.querySelector('.sepay-details');

        if (tabs.length >= 2) {
            tabs[0].addEventListener('click', function() {
                tabs[0].classList.add('active');
                tabs[1].classList.remove('active');
                qrContainer.style.display = 'flex';
                detailsContainer.style.display = 'block';
            });

            tabs[1].addEventListener('click', function() {
                tabs[1].classList.add('active');
                tabs[0].classList.remove('active');
                qrContainer.style.display = 'none';
                detailsContainer.style.display = 'block';
            });
        }

        const copyButtons = document.querySelectorAll('[data-bb-toggle="copy"]');

        copyButtons.forEach((button) => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const textToCopy = this.getAttribute('data-clipboard');

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(textToCopy);
                } else {
                    fobUnsecuredCopyToClipboard(textToCopy);
                }

                const originalIcon = this.innerHTML;
                this.innerHTML = `<x-core::icon name="ti ti-check" />`;

                setTimeout(() => {
                    this.innerHTML = originalIcon;
                }, 1500);
            });
        });
    });

    let interval = null

    $(document).ready(function() {
        const paymentStatus = $('[data-bb-toggle="sepay-transaction-status"]')

        if (paymentStatus.length) {
            interval = setInterval(() => fetchPaymentStatus(paymentStatus), 3000)
        }
    });

    function fetchPaymentStatus(elm) {
        $.ajax({
            url: elm.data('url'),
            method: 'POST',
            data: {
                charge_id: elm.data('charge-id')
            },
            success: ({
                data
            }) => {
                if (data.status.value === 'completed') {
                    $('#sepay-transaction-status-done').show()
                    $('#sepay-bank-info').remove()

                    let paymentStatusElement = $(document).find(
                        'span[data-bb-target="ecommerce-order-payment-status"]');

                    if (paymentStatusElement.length && data.status_html) {
                        paymentStatusElement.html(data.status_html);
                    }

                    clearInterval(interval)
                }
            }
        })
    }

    function fobUnsecuredCopyToClipboard(textToCopy) {
        const textArea = document.createElement('textarea');
        textArea.value = textToCopy;
        textArea.style.position = 'absolute';
        textArea.style.left = '-999999px';
        document.body.append(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand('copy');
        } catch (error) {
            console.error('Unable to copy to clipboard', error);
        }

        document.body.removeChild(textArea);
    }
</script>
