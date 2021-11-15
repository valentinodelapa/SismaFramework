$(document).ready(function () {

    $('.open-modal').on('click', function () {
        openModal();
    });

    $('.close-modal').on('click', function () {
        closeModal();
    });
    $('.modal-background').on('click', function (data) {
        if (data.target == this) {
            closeModal();
        };
    });

});


function openModal() {
    $('.modal-background').fadeIn();
}

function closeModal() {
    $('.modal-background').fadeOut();
}