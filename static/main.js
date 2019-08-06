new QRCode(document.getElementById("qrcode"), document.getElementById('secret').innerText);

let scanner = new Instascan.Scanner({ video: document.getElementById('preview') });
scanner.addListener('scan', submitCode);

Instascan.Camera.getCameras()
.then(function (cameras) {
    if (cameras.length > 0) {
        scanner.start(cameras[0]);
    }
    setCamera(cameras.length > 0);
}).catch(function (e) {
    setCamera(false);
});

function setCamera(isCamera) {
    if(isCamera) {
        document.getElementById('manual').hidden = true;
        document.getElementById('preview').hidden = false;
    } else {
        document.getElementById('manual').hidden = false;
        document.getElementById('preview').hidden = true;
    }
}

function submitCode(secret) {
    secret = secret || document.getElementById('code').value;
    fetch('kill.php', {
        method: 'POST',
        body: JSON.stringify({secret})
    })
    .then(resp => resp.json())
    .then(handleKill)
    .catch(err => alert(err));
}

function handleKill(resp) {
    if(resp.error != null) {
        alert(resp.error);
    } else if(resp.code === 3) {
        window.location = window.location;
    }
}