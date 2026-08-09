(function ($) {
  'use strict';
  // Define const
  const amountMin = 1000;
  const amountMax = 300000;
  const ageMin = 18;
  const ageMax = 65;

  $(function () {
    var rangeWrapperFilterType = $('.wf-range-wrapper').data('filter');
    var amountSliderEl = document.getElementById('wf_range_amount');
    var ageSliderEl = document.getElementById('wf_range_age');
    // Guard: rangeslider-markup'en findes ikke i "no rangeslider"-builds.
    // Uden dette tjek kastede scriptet en TypeError på ALLE sider (v3.1.35).
    if (!amountSliderEl || !ageSliderEl || typeof window.noUiSlider === 'undefined') {
      return;
    }
    const currencyLabel = amountSliderEl.dataset.label
    const commonLabel = amountSliderEl.dataset.common
    const ageLabel = ageSliderEl.dataset.label
    let amountRange = {
      'min': [0],
      '10%': [1000, 500],
      '40$': [50000, 10000],
      '60%': [100000, 10000],
      'max': [amountMax]
    }
    if (rangeWrapperFilterType === "max") {
      amountRange = {
        'min': [0],
        '1%': [100, 50],
        '5%': [500, 100],
        '10%': [1000, 500],
        '40$': [50000, 10000],
        '60%': [100000, 10000],
        'max': [amountMax]
      }
    }
    // Create Slider
    var amountSlider = noUiSlider.create(amountSliderEl, {
      start: 0,
      connect: 'lower',
      step: 1000,
      range: amountRange
    });
    var ageSlider = noUiSlider.create(ageSliderEl, {
      start: ageMin - 1,
      connect: 'lower',
      step: 1,
      range: {
        'min': ageMin - 1,
        '10%': [ageMin + 1],
        '50%': [ageMin + 15],
        'max': ageMax
      }
    });
    // Eventlistener whenever a slider is changed.
    var amount = null;
    var age = ageMax;
    amountSlider.on('change.one', function (values) {
      doFilterFeeds(0);
    });
    ageSlider.on('change.one', function (values) {
      doFilterFeeds(1);
    });
    amountSlider.on('update.one', function (values) {
      amount = parseInt(values[0]);
      var str;
      if (amount == 0) {
        amount = null;
        str = commonLabel;
      } else if (amount <= amountMax) {
        str = amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') + ' ' + currencyLabel;
        str = amount == amountMax ? '+' + str : str;
      }
      $('#wf_amount_value').html(str);
    });
    ageSlider.on('update.one', function (values) {
      age = values[0];
      var str;
      if (age == (ageMin - 1)) {
        age = ageMax;
        str = commonLabel;
      } else if (age <= ageMax) {
        var str = parseInt(age).toString() + ' ' + ageLabel;
        str = age == ageMax ? '+' + str : str;
      }
      $('#wf_age_value').html(str);
    });

    // Filter feeds.
    function doFilterFeeds(order) {
      var allFeeds = $('#campaigns-weedget .card');
      var isNoResult = true;
      if (amount == null && age == null) {
        allFeeds.each((index, feed) => {
          $(feed).show();
        });
      } else {
        allFeeds.each((index, feed) => {
          var amountEl = $(feed).find('tbody tr:nth-child(1) th');
          var ageEl = $(feed).find('tbody tr:nth-child(3) th');
          if (amountEl && ageEl) {
            var amountStr = amountEl.text();
            var range = amountStr.split('-');
            var minAmount = parseInt(range[0]);
            var maxAmount = parseInt(range[1]);
            var ageStr = ageEl.text();
            var ageOnFeed = parseInt(ageStr);
            if (amount == null) {
              if (age >= ageOnFeed) {
                $(feed).show();
                isNoResult = false;
              } else {
                $(feed).hide();
              }
            } else {
              if ((minAmount <= amount && maxAmount >= amount) && age >= ageOnFeed) {
                if ((amount <= 50000 && maxAmount <= 50000) || (amount >= 50000 && maxAmount >= amount)) {
                  $(feed).show();
                  isNoResult = false;
                } else {
                  $(feed).hide();
                }
              } else {
                $(feed).hide();
              }
            }
          }
        });
      }

      // Display all feeds when no result.
      if (isNoResult) {
        allFeeds.each((index, feed) => {
          $(feed).show();
        });
      }
    }
  });
})(jQuery);
