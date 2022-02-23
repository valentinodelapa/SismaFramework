$(document).ready(function () {
    
    $(".continuation-confirmation").click(function () {
        return confirm($("#continuation-confirmation-message").val());
    });
    
});