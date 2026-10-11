@props(['panel' => false])
@if ($gtm = gtmContainerId($panel))
<!-- Google Tag Manager: only after the visitor accepts analytics cookies (see the cookie banner) -->
<script>
(function (w, d, s, l, i) {
    var loaded = false;
    function load() {
        if (loaded) { return; } loaded = true;
        w[l] = w[l] || []; w[l].push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
        var f = d.getElementsByTagName(s)[0], j = d.createElement(s);
        j.async = true; j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i;
        f.parentNode.insertBefore(j, f);
    }
    var ok = false;
    try { ok = localStorage.getItem('ht_cookie_ok') === 'all'; } catch (e) {}
    if (ok) { load(); } else { w.addEventListener('ht:cookies-accepted', load, {once: true}); }
})(window, document, 'script', 'dataLayer', '{{ $gtm }}');
</script>
<!-- End Google Tag Manager -->
@endif
