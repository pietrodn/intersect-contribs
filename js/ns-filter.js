$(function() {
    $('#wikiDb').on('change', generateNamespaceChooser);

    // On page load, a project can be already selected.
    generateNamespaceChooser();
});

function generateNamespaceChooser() {
    clearNamespaces();
    var wikiDomain = $("#wikiDb option:selected").text();

    if(wikiDomain == 'select a wiki') {
        // Project not selected, returning
        return;
    }

    $.ajax({
        url: '//' + wikiDomain +  '/w/api.php?format=jsonty&action=query&meta=siteinfo&siprop=namespaces',
        data: {
            format: 'json'
        },
        dataType: 'jsonp'
    }).done( function ( data ) {
        var namespaces = data.query.namespaces;

        for(var i in namespaces) {
            var txt = String(i) + ' - ' + namespaces[i]['*'] + ' (' + namespaces[i].canonical + ')';
            var option = $('<option></option>').val(String(i)).text(txt);
            $("#namespaceFilter").append(option);
        }
    } );
}

function clearNamespaces() {
    $('#namespaceFilter > option:not(#allNamespaces)').remove();
}
