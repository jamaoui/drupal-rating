(function ($) {
  'use strict';

  Drupal.RatingModule = {
    stars: {
      'changeStar': function (element, html) {
        var star = $(element);
        var newStar = this.__prepareStar(html);
        if (star.length) {
          return star.replaceWith(newStar);
        }

      },
      '__prepareStar': function (html, datas) {
        var $me = $(html);
        if ($me.length) {
          $me = $me.css('pointer-events', 'none');
          this.__setData($me, datas);
        }
        return $me;
      },
      '__setData': function (element, datas) {
        $.each(datas, function (i, val) {
          element.data(val.name, val.value);
        });
      }
    },
  };

})(jQuery);