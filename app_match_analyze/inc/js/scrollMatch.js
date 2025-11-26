//*******************************************************
// Scroll trough the match with live updates statistics
// **************************************************** */
let autoScrollInterval = null;
let currentPercent = 0;

//Event handlers
$(document).ready(function() {
  $('.header_points').on('click', () => toggleSlider());
  $('.div_slider').hide();
  $('#point_slider').on('input', (e) => { change_slider(e.currentTarget.value); });
  $('#point_slider').on('pointermove', (e) => { change_slider(e.currentTarget.value); });

  function toggleSlider() {
      $('.div_slider').toggle();
      $('.header_players').toggle();
      if('none' === $('.div_slider').css('display')) {
          stopAutoScroll();
          match.scrollToPercent(100);
          currentPercent = 0;
          update_stats();
          $('.slider').val(100);
          $('#btnAutoScroll').text("▶️");
      } else {
          startAutoScroll();
          $('#btnAutoScroll').text("⏸️");
      }
    }
  
    // Button Event
    $('#btnAutoScroll').on('click', function() {
        const $btn = $(this);
        if (autoScrollInterval) {
            stopAutoScroll();
            $btn.text("▶️");
        } else {
            startAutoScroll();
            $btn.text("⏸️");
        }
  });
});

function startAutoScroll() {
    if (autoScrollInterval) return; // läuft bereits

    autoScrollInterval = setInterval(() => {
        currentPercent += 1;

        if (currentPercent > 100) {
            // Stoppe Interval
            clearInterval(autoScrollInterval);
            autoScrollInterval = null;

            // 5 Sekunden Pause bei 100%
            setTimeout(() => {
                currentPercent = 0;
                match.scrollToPercent(currentPercent);
                $('#point_slider').val(currentPercent);
                change_slider(currentPercent);

                // 2 Sekunden Pause bei 0%
                setTimeout(() => {
                    startAutoScroll(); // Auto-Scroll erneut starten
                }, 2000);

            }, 5000);

            return;
        }

        match.scrollToPercent(currentPercent);
        $('#point_slider').val(currentPercent);
        change_slider(currentPercent);

    }, 200);
}

function stopAutoScroll() {
    if (autoScrollInterval) {
        clearInterval(autoScrollInterval);
        autoScrollInterval = null;
    }
}

function change_slider(value) {
  match.scrollToPercent(parseInt(value));
  update_stats();
}

function change_slider(value) {
  value = parseInt(value, 10);
  currentPercent = value;
  match.scrollToPercent(value);
  update_stats();
}
//*******************************************************
