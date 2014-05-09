/* Not centrally logged in */
var t=new Date();t.setTime(t.getTime()+86400000);if('localStorage'in window&&window.localStorage){localStorage.setItem('CentralAuthAnon',t.getTime());}else{document.cookie='CentralAuthAnon=1; expires='+t.toGMTString()+'; path=/';}
/* cache key: loginwiki:centralauth:minify-js:5bc41e99179ba40a7c116ec8295e74e4 */