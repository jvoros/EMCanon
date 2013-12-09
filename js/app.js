var baseURL = 'http://localhost/emcanon';

// EVENT HANDLERS

$('.interact-vote').click(function(e) {
    e.preventDefault();
    addVote($(this));
});
        
$('#add-article').submit(function(e) {
    e.preventDefault();
    addArticle($(this));
});


// AJAX HANDLERS 

function addArticle(addForm) {
    $.ajax({
        type: 'POST',
        url: baseURL + '/interact/submit',
        data: addForm.serialize(),
        success: function (data) {
            $('.add-response').html(data);
            $('#add-PMID').val('');
        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert(textStatus);
        }
    });
}

function addVote(voteAnchor) {
    $.ajax({
        type: 'POST',
        url: voteAnchor.attr('href'),
        success: function (data) {
            voteAnchor.next().text(Number(voteAnchor.next().text()) + 1);
            voteAnchor.closest('ul').find('.interact-vote').removeAttr('href');
        }
    });
}
