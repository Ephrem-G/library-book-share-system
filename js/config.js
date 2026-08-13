/* ============================================
   Library Book Share - Front-end Configuration
   --------------------------------------------
   One place to change settings used by the
   browser code. This file is loaded BEFORE
   js/api.js and js/app.js in dashboard.html.

   googleMapsApiKey:
     Paste your own key from
     https://console.cloud.google.com/google/maps-apis
     Leave it as '' to disable the map gracefully
     (a placeholder card is shown instead of a
     broken Google watermark).

   apiBase:
     Path prefix for fetch() calls. Leave as ''
     when the PHP backend is served from the same
     folder (the normal XAMPP setup).
   ============================================ */
window.APP_CONFIG = {
  googleMapsApiKey: 'AIzaSyDywjE6MaXbGmu8UbBf5wcSCaYNOnT-nRk',
  apiBase: ''
};
