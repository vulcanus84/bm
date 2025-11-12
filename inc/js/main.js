$(document).ready(function() {
  const HEARTBEAT_INTERVAL = 60000;
  var rootPath = window.location.origin
  if(rootPath=='http://localhost:8888') { rootPath += '/bm/inc/'; } else { rootPath+=  '/inc/'; }

  // Prüfen, ob gerade die Login-Maske angezeigt wird
  const bodyHasLoginForm = $('form#login').length > 0;

  // Wenn Login-Maske sichtbar ist → Heartbeat aussetzen
  if (bodyHasLoginForm) {
    console.log("Heartbeat deaktiviert (Login-Maske aktiv).");
    return;
  }

  function checkSession() {
      $.ajax({
          url: rootPath + 'heartbeat.php',
          type: 'GET',
          cache: false,
          dataType: 'json',
          success: function(response) {
              if (response.status === 'expired') {
                  window.location.reload();
              }
          },
          error: function() {
              console.warn("Heartbeat-Check fehlgeschlagen");
          }
      });
  }

  // Direkt beim Laden prüfen
  checkSession();

  // Danach regelmäßig wiederholen (nur wenn kein Login-Formular sichtbar ist)
  const heartbeatTimer = setInterval(checkSession, HEARTBEAT_INTERVAL);

  // Beim Reaktivieren des Tabs sofort prüfen
  document.addEventListener('visibilitychange', function() {
      if (!document.hidden) {
          checkSession();
      }
  });
});
