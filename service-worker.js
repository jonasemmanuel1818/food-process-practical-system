const CACHE_NAME = "food-process-system-v3";

const APP_SHELL = [

    // Main pages
    "/food_process_system/",
    "/food_process_system/index.php",
    "/food_process_system/offline.html",
    "/food_process_system/offline-learning.html",
    "/food_process_system/manifest.json",

    // Main CSS
    "/food_process_system/assets/css/style.css",

    // Simulation JavaScript
    "/food_process_system/assets/js/practical2_simulation.js",
    "/food_process_system/assets/js/practical3_simulation.js",
    "/food_process_system/assets/js/practical4_simulation.js",
    "/food_process_system/assets/js/practical5_filtration.js",

    // PWA icons
    "/food_process_system/assets/icons/icon-192.png",
    "/food_process_system/assets/icons/icon-512.png",

    // Offline Practical Learning Pages
    "/food_process_system/offline/practical1.html",
    "/food_process_system/offline/practical2.html",
    "/food_process_system/offline/practical3.html",
    "/food_process_system/offline/practical4.html",
    "/food_process_system/offline/practical5.html"

];


// =========================================================
// INSTALL
// =========================================================

self.addEventListener("install", function (event) {

    event.waitUntil(

        caches.open(CACHE_NAME)

            .then(function (cache) {

                return cache.addAll(APP_SHELL);

            })

            .catch(function (error) {

                console.error(
                    "Food Process System cache installation failed:",
                    error
                );

            })

    );

    // Activate the new service worker immediately
    self.skipWaiting();

});


// =========================================================
// ACTIVATE
// =========================================================

self.addEventListener("activate", function (event) {

    event.waitUntil(

        caches.keys()

            .then(function (cacheNames) {

                return Promise.all(

                    cacheNames.map(function (cacheName) {

                        if (

                            cacheName !== CACHE_NAME &&

                            cacheName.startsWith(
                                "food-process-system-"
                            )

                        ) {

                            return caches.delete(cacheName);

                        }

                    })

                );

            })

    );

    // Take control of open pages immediately
    self.clients.claim();

});


// =========================================================
// FETCH
// =========================================================

self.addEventListener("fetch", function (event) {

    // Only handle GET requests
    if (event.request.method !== "GET") {

        return;

    }


    const requestURL =
        new URL(event.request.url);


    // Only handle requests from this application
    if (

        requestURL.origin !== self.location.origin ||

        !requestURL.pathname.startsWith(
            "/food_process_system/"
        )

    ) {

        return;

    }


    // -----------------------------------------------------
    // PHP pages
    // -----------------------------------------------------
    //
    // We do NOT cache logged-in PHP pages because they depend
    // on sessions and database information.
    //
    // index.php is allowed because it is the public landing
    // page.
    //

    if (

        requestURL.pathname.endsWith(".php") &&

        !requestURL.pathname.endsWith("/index.php")

    ) {

        return;

    }


    event.respondWith(

        caches.match(event.request)

            .then(function (cachedResponse) {

                // Use cached version when available
                if (cachedResponse) {

                    return cachedResponse;

                }


                // Otherwise try the network
                return fetch(event.request)

                    .then(function (networkResponse) {

                        if (

                            !networkResponse ||

                            networkResponse.status !== 200 ||

                            networkResponse.type !== "basic"

                        ) {

                            return networkResponse;

                        }


                        // Save successful response
                        const responseClone =
                            networkResponse.clone();


                        caches.open(CACHE_NAME)

                            .then(function (cache) {

                                cache.put(
                                    event.request,
                                    responseClone
                                );

                            });


                        return networkResponse;

                    })


                    .catch(function () {

                        // If the requested page is unavailable,
                        // show the custom offline page.

                        return caches.match(
                            "/food_process_system/offline.html"
                        );

                    });

            })

    );

});