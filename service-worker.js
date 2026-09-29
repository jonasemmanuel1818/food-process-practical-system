const CACHE_NAME = "food-process-system-v2";

const APP_SHELL = [
    "/food_process_system/",
    "/food_process_system/index.php",
    "/food_process_system/offline.html",
    "/food_process_system/manifest.json",

    "/food_process_system/assets/css/style.css",

    "/food_process_system/assets/js/practical2_simulation.js",
    "/food_process_system/assets/js/practical3_simulation.js",
    "/food_process_system/assets/js/practical4_simulation.js",
    "/food_process_system/assets/js/practical5_filtration.js",

    "/food_process_system/assets/icons/icon-192.png",
    "/food_process_system/assets/icons/icon-512.png"
];


/*
|--------------------------------------------------------------------------
| INSTALL
|--------------------------------------------------------------------------
*/

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

    self.skipWaiting();

});


/*
|--------------------------------------------------------------------------
| ACTIVATE
|--------------------------------------------------------------------------
*/

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

    self.clients.claim();

});


/*
|--------------------------------------------------------------------------
| FETCH
|--------------------------------------------------------------------------
*/

self.addEventListener("fetch", function (event) {

    /*
     * Only handle GET requests.
     */

    if (event.request.method !== "GET") {
        return;
    }


    const requestURL = new URL(event.request.url);


    /*
     * Only handle requests belonging to our application.
     */

    if (
        requestURL.origin !== self.location.origin ||
        !requestURL.pathname.startsWith(
            "/food_process_system/"
        )
    ) {

        return;

    }


    /*
     * PHP pages other than index.php should normally
     * remain network-dependent because they may contain
     * login sessions, database information and
     * user-specific content.
     */

    if (
        requestURL.pathname.endsWith(".php") &&
        !requestURL.pathname.endsWith("/index.php")
    ) {

        return;

    }


    event.respondWith(

        caches.match(event.request)

            .then(function (cachedResponse) {

                /*
                 * Return cached version when available.
                 */

                if (cachedResponse) {

                    return cachedResponse;

                }


                /*
                 * Otherwise try the network.
                 */

                return fetch(event.request)

                    .then(function (networkResponse) {

                        /*
                         * Only cache successful responses.
                         */

                        if (
                            !networkResponse ||
                            networkResponse.status !== 200 ||
                            networkResponse.type !== "basic"
                        ) {

                            return networkResponse;

                        }


                        /*
                         * Store a copy in the cache.
                         */

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

                        /*
                         * If the network is unavailable,
                         * show the offline page.
                         */

                        return caches.match(
                            "/food_process_system/offline.html"
                        );

                    });

            })

    );

});