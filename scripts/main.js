new QRCode(document.getElementById("qrcode"), document.getElementById('secret').innerText);

let scanner = new Instascan.Scanner({ video: document.getElementById('preview') });
scanner.addListener('scan', submitCode);

Instascan.Camera.getCameras()
    .then(function (cameras) {
        if (cameras.length > 0) {
            scanner.start(cameras[0]);
        } else {
            alert('No cameras found.');
        }
    }).catch(function (e) {
        console.error(e);
    });

function submitCode(secret) {
    fetch('kill.php', {
        method: 'POST',
        body: JSON.stringify({secret})
    })
    .then(resp => resp.json())
    .then(json => alert(JSON.stringify(json)))
    .error(err => alert(err));
}
