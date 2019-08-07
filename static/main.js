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
    document.getElementById('manual').hidden = isCamera;
    document.getElementById('preview').hidden = !isCamera;
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
        document.getElementById('errorMessage').innerText = resp.error;
        $('#failModal').modal();
    } else if(resp.code === 3) {
        $('#killModal').modal();
    }
}

// Jag är inte stolt över detta
function checkAlive() {
    fetch('alive.php')
    .then(resp => resp.json())
    .then(json => {
        if(json.alive != '1') window.location = window.location 
    })
}

setInterval(checkAlive, 1000 * 30);