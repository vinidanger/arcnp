import QRCode from 'qrcode';

// otpauth:// é só texto — o QR em si nunca passa pelo servidor como
// imagem, é desenhado aqui direto no navegador a partir do link (ver
// TwoFactorAuthenticationService::generateQrCodeUri() no back-end).
const canvas = document.getElementById('two-factor-qr-canvas');

if (canvas) {
    QRCode.toCanvas(canvas, canvas.dataset.otpauthUri, { width: 200 }, function (error) {
        if (error) {
            canvas.replaceWith(document.createTextNode('Não foi possível gerar o QR code — use a chave manual abaixo.'));
        }
    });
}
