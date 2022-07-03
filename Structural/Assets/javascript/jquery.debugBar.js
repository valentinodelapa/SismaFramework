$(document).ready(function () {
    
    var debugBarBody = $('.debug-bar-body');
    
    $('.debug-bar').on('mouseover', function(){
        $(this).css('height', 'auto');
    }).on('mouseleave', function(){
        $(this).css('height', '0px');
    });
    
    $('.debug-information-label').on('click', function () {
        var id = $(this).attr('id');
        var informationType = id.split('-').pop();
        $.each(debugBarBody, function () {
            if ($(this).attr('id') !== 'debug-body-' + informationType) {
                $(this).hide();
            }
        });
        $('#debug-body-' + informationType).toggle();
    });
    
});