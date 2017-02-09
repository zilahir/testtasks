$(document).ready(function() {
  var numberOfListItem = 0;
  $("#searchitem").click(function(e) {
      e.preventDefault();
      $("#searchform").toggleClass("hidden");
  });
    $("#addnewitem").click(function(e) {
        e.preventDefault();
        // shows form for adding new item to shopping list
        $("#newitemadding").toggleClass("hidden");
        // changes the icon
        $("#addnewitem").toggleClass("fa-minus");
    });
    $("#addnewitembtn").click(function(e) {
        e.preventDefault();
        // get current amount of items
        numberOfListItem = $("#list-items li").length;
        var newNumberOfListItem = numberOfListItem + 1;
        var newItem = $("#newitemvalue").val();
        var newItemDom = '<li data-id="' + newNumberOfListItem + '" class="list-group-item">' + newItem + '<span class="badge del-item"><i class="fa fa-times"></i></span></li>';
        $("#list-items").prepend(newItemDom);
        $("[data-id=" + newNumberOfListItem + "]").click(function(e) {
                var currentCounter = newNumberOfListItem;
                currentCounter = currentCounter-1;
                var itemToDel = ($(this));
                var idToDel = $(this).data("id");
                itemToDel.fadeOut(); //removing the item from the list
            })
            // empty the input box
        $("#newitemvalue").val("");
    });

    $('#searchinput').on('input', function() {
    // implement searching
    var searchString = $("#searchinput").val();
    var allItem = $("#list-items li");
    $.each(allItem, function(index, element) {
          //var n = searchString.indexOf($(this).text());
          var n = $(this).text().indexOf(searchString);
          //console.log(n);
          if (n!== -1) {
            $(this).removeClass("hidden");
          } else {
            $(this).addClass("hidden");
          }

      });
    });


});
