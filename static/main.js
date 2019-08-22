new QRCode(document.getElementById("qrcode"), document.getElementById('secret').innerText)

let scanner = new Instascan.Scanner({ video: document.getElementById('preview') })
scanner.addListener('scan', submitCode)

Instascan.Camera.getCameras()
    .then(function (cameras) {
        if (cameras.length > 0) {
            var camIndex = 0
            $.each(cameras, (i, c) => {
                if (c.name.indexOf('back') != -1) {
                    camIndex = i
                    return false
                }
            })
            scanner.start(cameras[camIndex])
        }
        setCamera(cameras.length > 0)
    }).catch(function (e) {
        setCamera(false)
    })

function setCamera(isCamera) {
    $('#manual').attr('hidden', isCamera)
    $('#auto').attr('hidden', !isCamera)
}

function submitCode(secret) {
    secret = secret || $('#code').val()
    fetch('kill.php', {
        method: 'POST',
        body: JSON.stringify({secret}),
        credentials: "same-origin"
    })
    .then(resp => resp.json())
    .then(handleKill)
    .catch(err => alert('Något gick fel. ' + err))
}

function handleKill(resp) { // det hette qrkill förut, heheheh
    if(resp.error) {
        $('#modal-title').text('Fel...')
        $('#modal-message').text(resp.error)
    } else if(resp.success) {
        $('#modal-title').text('Grattis!')
        $('#modal-message').text(resp.success)
    }
    $('#qrtag-modal').modal()
}

// Jag är inte stolt över detta
function checkAlive() {
    fetch('alive.php', {
        credentials: "same-origin"
    })
    .then(resp => resp.json())
    .then(json => {
        if(json.alive != '1') location.reload()
    })
}

setInterval(checkAlive, 1000 * 10)