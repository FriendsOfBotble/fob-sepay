<style>
    .sepay-container {
        max-width: 700px;
        margin: 2rem 0;
    }

    .sepay-card {
        background-color: var(--bs-body-bg, #fff);
        border-radius: 12px;
        border: 1px solid var(--bs-primary);
        padding: 28px;
        margin-bottom: 28px;
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

    .sepay-qr-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 28px;
    }

    .sepay-qr-code {
        width: 300px;
        height: 300px;
        border-radius: 12px;
        margin-top: 0.5rem;
    }

    .sepay-qr-caption {
        font-size: 14px;
        color: var(--bs-secondary-color, #6c757d);
        text-align: center;
        font-weight: 500;
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
        margin-top: 8px;
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

    .sepay-copy-btn svg {
        width: 16px;
        height: 16px;
        color: var(--bs-secondary-color, #6c757d);
    }

    .sepay-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        padding: 28px;
        background-color: var(--bs-tertiary-bg, #f8f9fa);
        border-radius: 12px;
        margin-top: 28px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .sepay-loading-status {
        font-weight: 600;
        font-size: 16px;
        color: var(--bs-heading-color, #333);
    }

    .sepay-loading-info {
        font-size: 14px;
        color: var(--bs-secondary-color, #6c757d);
        margin-top: 4px;
    }

    .sepay-loading-progress {
        width: 100%;
        height: 4px;
        background-color: rgba(13, 110, 253, 0.1);
        border-radius: 2px;
        overflow: hidden;
        margin-top: 8px;
    }

    .sepay-loading-progress-bar {
        height: 100%;
        width: 30%;
        background-color: var(--primary-color, #0d6efd);
        border-radius: 2px;
        animation: sepay-progress 2s infinite;
    }

    @keyframes sepay-spin {
        to { transform: rotate(360deg); }
    }

    @keyframes sepay-progress {
        0% { width: 0%; }
        50% { width: 70%; }
        100% { width: 100%; }
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
        font-size: 28px;
        color: #198754;
        margin-bottom: 0;
    }

    .sepay-bank-logo {
        width: 60px;
        height: 60px;
    }

    .sepay-download-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background-color: var(--bs-body-bg, #fff);
        border: 1px solid var(--bs-border-color, #dee2e6);
        color: var(--bs-secondary-color, #6c757d);
        font-size: 12px;
        font-weight: 500;
        padding: 4px 10px;
        border-radius: 6px;
        cursor: pointer;
        margin-top: 10px;
        transition: all 0.2s ease;
    }

    .sepay-download-btn:hover {
        background-color: var(--bs-tertiary-bg, #f8f9fa);
        color: var(--primary-color, #0d6efd);
        border-color: var(--primary-color, #0d6efd);
    }

    .sepay-download-btn svg {
        width: 16px;
        height: 16px;
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
                    <span>Thông tin thanh toán</span>
                </div>

                <div class="row">
                    <div class="col-md-5 sepay-qr-container">
                        <div class="sepay-qr-caption">Quét mã QR bằng ứng dụng ngân hàng hoặc ví điện tử</div>
                        <div class="sepay-qr-code">
                            <img src="{{ $qrCodeUrl }}" alt="QR Code" width="100%" height="auto" id="qrCodeImage">
                        </div>
                        <button class="sepay-download-btn" id="downloadQrCode">
                            <x-core::icon name="ti ti-download" />
                            Tải mã QR
                        </button>
                    </div>

                    <div class="col-md-7 sepay-details">
                        @if(isset($bankInfo['bankLogo']) && $bankInfo['bankLogo'])
                            <img src="{{ $bankInfo['bankLogo'] }}" alt="{{ $bankInfo['bank'] }}" class="sepay-bank-logo">
                        @endif
                        <div class="sepay-detail-row">
                            <div class="sepay-detail-label">Tên Ngân Hàng</div>
                            <div class="sepay-detail-value">{{ $bankInfo['bank'] }}</div>
                        </div>
                        <div class="sepay-detail-row">
                            <div class="sepay-detail-label">Chủ Tài Khoản</div>
                            <div class="sepay-detail-value">{{ $bankInfo['bankAccountHolder'] }}</div>
                        </div>
                        <div class="sepay-detail-row">
                            <div class="sepay-detail-label">Số Tài Khoản</div>
                            <div class="sepay-detail-value">
                                {{ $bankInfo['bankAccountNumber'] }}
                                <button class="sepay-copy-btn" data-clipboard="{{ $bankInfo['bankAccountNumber'] }}" data-bb-toggle="copy">
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
                        <div class="sepay-warning">
                            <p>Vui lòng giữ nguyên nội dung chuyển khoản <strong>{{ $chargeId }}</strong> và nhập đúng số tiền <strong>{{ $formattedOrderAmount }}</strong> để được xác nhận thanh toán tự động.</p>
                        </div>
                    </div>
                </div>

                <div class="sepay-loading" data-bb-toggle="sepay-transaction-status" data-url="{{ route('sepay.transactions.check') }}" data-charge-id="{{ $chargeId }}">
                    <div>
                        <div class="sepay-loading-status">Đang chờ thanh toán</div>
                        <div class="sepay-loading-info">Hệ thống tự động kiểm tra giao dịch</div>
                    </div>
                    <div class="sepay-loading-progress">
                        <div class="sepay-loading-progress-bar"></div>
                    </div>
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
        const downloadQrCodeBtn = document.getElementById('downloadQrCode');
        const qrCodeImage = document.getElementById('qrCodeImage');

        if (downloadQrCodeBtn && qrCodeImage) {
            downloadQrCodeBtn.addEventListener('click', function() {
                const imageSrc = qrCodeImage.getAttribute('src');

                const downloadImage = (imgUrl, filename) => {
                    fetch(imgUrl)
                        .then(response => response.blob())
                        .then(blob => {
                            const blobUrl = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.style.display = 'none';
                            a.href = blobUrl;
                            a.download = filename || 'qr-code.png';
                            document.body.appendChild(a);
                            a.click();
                            URL.revokeObjectURL(blobUrl);
                            document.body.removeChild(a);

                            downloadQrCodeBtn.innerHTML = `<x-core::icon name="ti ti-check" /> Đã tải xuống`;
                            setTimeout(() => {
                                downloadQrCodeBtn.innerHTML = `<x-core::icon name="ti ti-download" /> Tải mã QR`;
                            }, 1500);
                        })
                        .catch(() => {
                            const a = document.createElement('a');
                            a.href = imgUrl;
                            a.download = 'qr-code.png';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                        });
                };

                const filename = `qr-code-${('{{ $chargeId }}').substring(0, 8)}.png`;
                downloadImage(imageSrc, filename);
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
