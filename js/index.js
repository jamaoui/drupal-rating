(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.RatingBehavior = {
    attach: function (context, settings) {
      var $context = $(context);
      $context.find('input.form-rating-range', context).once('RatingBehavior').each(function () {
        var $RatingFormContext = $(context);

        var $RatingFormRangeContext = $(context);
        var RatingBlock = drupalSettings.RatingBlock;
        var primaryInputConfig = RatingBlock.form.primaryInput;
        var primaryInputSelector;

        if (primaryInputConfig.isInput) {
          primaryInputSelector = 'input[name="' + primaryInputConfig.name + '"]';
        }
        else {
          primaryInputSelector = primaryInputConfig.type + '[name="' + primaryInputConfig.name + '"]';
        }

        var primaryInputDom = $RatingFormRangeContext.find(primaryInputSelector);

        var StarsContainer = $RatingFormRangeContext.find('.rating-stars-container');
        var StarIcons = RatingBlock.icons.star;


        var RatingModuleStarJS = Drupal.RatingModule.stars;
        var currentValues = {
          nbStars: RatingBlock.nbStars,
          isUserRated: RatingBlock.isUserRated,
        };
        var permissions = RatingBlock.permissions;


        var MAX_STARS = RatingBlock.MAX_STARS;

        var RatingStarClassEvent = 'rating-star-event';
        var RatingStarClassEventDot = '.' + RatingStarClassEvent;
        var StarDataName = 'rating-star';
        var StepValue = primaryInputConfig.step;

        var userCurrentRating = currentValues.isUserRated * MAX_STARS;

        // Empty the container from stars
        StarsContainer.empty();
        for (var i = 0; i < MAX_STARS; i++) {
          var obj = $('<span class="btn px-1"></span>').addClass(RatingStarClassEvent);
          var datas = [{name: StarDataName, value: i + 1}];
          var star;
          if (i < userCurrentRating) {
            star = RatingModuleStarJS.__prepareStar(StarIcons.full, datas);
          }
          else {
            star = RatingModuleStarJS.__prepareStar(StarIcons.empty, datas);
          }
          obj.html(star);
          obj.click(function (e) {
            primaryInputDom.form().submit();
          });
          StarsContainer.append(obj);
        }

        StarsContainer.find(RatingStarClassEventDot)
            .mouseover(function (e) {
              if ($(e.target).hasClass(RatingStarClassEvent)) {
                var currentStar = $(this).children();
                var CurrentStarData = (currentStar.data(StarDataName));
                StarsContainer.find(RatingStarClassEventDot).each(function (index, element) {
                  index++;
                  element = $(element);
                  var oldStar = element.children();
                  var iconHtml = '';

                  var datas = [{name: StarDataName, value: index}];
                  if (CurrentStarData >= index) {
                    iconHtml = StarIcons.full;
                    primaryInputDom.val(CurrentStarData * StepValue);
                  }
                  else {
                    iconHtml = StarIcons.empty;
                  }
                  oldStar.detach();
                  element.append(RatingModuleStarJS.__prepareStar(iconHtml, datas));
                });
              }
            });

        if (userCurrentRating && !permissions.update) {
          StarsContainer.find(RatingStarClassEventDot).unbind('mouseover');
        }
      });
    }
  };
})(jQuery, Drupal);