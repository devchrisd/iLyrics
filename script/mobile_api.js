if (window.navigator && window.navigator.battery) {
   // API supported

   var battery = navigator.battery || navigator.webkitBattery || navigator.mozBattery || navigator.msBattery;

   // Print if battery is charging or not
    console.log("The battery is " + (navigator.battery.charging ? "" : "not") + " charging");
    console.log( "battery level: ", Math.floor(battery.level * 100) + "%" );

    var enableEffects = (battery.charging || battery.level > 0.25);

    // vibrate for one second
    if (enableEffects)
    {
        console.log( "Battery power is OK." );
    }
    else {

        if ('Notification' in window)
        {
            // API supported
            console.log( "Notification is supported!" );
            var notificationEvents = ['onclick', 'onshow', 'onerror', 'onclose'];
            // onclick: Fired when the user clicks on the notification.
            // onclose: Fired as soon as the user or the brower closes the notification.
            // onerror: Fired if an error occurs with the notification.
            // onshow: Fired when the notification is shown.

            // notification.onshow = function() {
            //   console.log('Notification shown');
            // };

            Notification.requestPermission(function(){
                var notification = new Notification(
                                            'BATTERY',
                                            {
                                              body: 'Battery power is critical!!'
                                            });
                notificationEvents.forEach(
                    function(eventName)
                    {
                        notification[eventName] = function(event)
                            {
                                console.log( 'Event "' + event.type + '" triggered for notification "' + notification.tag );
                            }; 
                    });
            });

        } else {
          // Not supported
        }

        console.log( "Battery power is critical!" );
    }

} else {
   // Not supported
}


if (window.navigator && window.navigator.vibrate) {
   // API supported
    navigator.vibrate(1000);
    console.log('vibrate supported.');
} else {
   // Not supported
}


if ('ondeviceorientation' in window) {
   // Event supported
    console.log('ondeviceorientation supported.');
} else {
   // Not supported
}

if (window.DeviceMotionEvent) {
   // Event supported
    console.log('DeviceMotionEvent supported.');
} else {
   // Not supported
}