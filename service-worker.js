const CACHE_NAME = "food-process-system-v3-2";


const APP_SHELL = [

    "/food_process_system/",
    "/food_process_system/index.php",
    "/food_process_system/offline.html",
    "/food_process_system/offline-learning.html",
    "/food_process_system/manifest.json",

    "/food_process_system/assets/css/style.css",

    "/food_process_system/assets/js/offline-progress.js",
    "/food_process_system/assets/js/offline-record.js",
    "/food_process_system/assets/js/offline-filtration.js",

    "/food_process_system/assets/js/practical2_simulation.js",
    "/food_process_system/assets/js/practical3_simulation.js",
    "/food_process_system/assets/js/practical4_simulation.js",
    "/food_process_system/assets/js/practical5_filtration.js",

    "/food_process_system/assets/icons/icon-192.png",
    "/food_process_system/assets/icons/icon-512.png",

    "/food_process_system/offline/practical1.html",
    "/food_process_system/offline/practical2.html",
    "/food_process_system/offline/practical3.html",
    "/food_process_system/offline/practical4.html",
    "/food_process_system/offline/practical5.html"

];


self.addEventListener(
    "install",
    function (event) {

        event.waitUntil(

            caches.open(CACHE_NAME)

                .then(function (cache) {

                    return cache.addAll(
                        APP_SHELL
                    );

                })

                .catch(function (error) {

                    console.error(
                        "Food Process System cache installation failed:",
                        error
                    );

                })

        );

        self.skipWaiting();

    }
);


self.addEventListener(
    "activate",
    function (event) {

        event.waitUntil(

            caches.keys()

                .then(function (cacheNames) {

                    return Promise.all(

                        cacheNames.map(
                            function (cacheName) {

                                if (
                                    cacheName !== CACHE_NAME &&
                                    cacheName.startsWith(
                                        "food-process-system-"
                                    )
                                ) {

                                    return caches.delete(
                                        cacheName
                                    );

                                }

                            }
                        )

                    );

                })

        );

        self.clients.claim();

    }
);


self.addEventListener(
    "fetch",
    function (event) {

        if (
            event.request.method !== "GET"
        ) {

            return;

        }


        const requestURL =
            new URL(event.request.url);


        if (
            requestURL.origin !==
                self.location.origin ||

            !requestURL.pathname.startsWith(
                "/food_process_system/"
            )
        ) {

            return;

        }


        /*
         * Keep the same PHP handling
         * that worked in v3.1.
         */

        if (
            requestURL.pathname.endsWith(".php") &&
            !requestURL.pathname.endsWith(
                "/index.php"
            )
        ) {

            return;

        }


        event.respondWith(

            caches.match(
                event.request
            )

                .then(function (cachedResponse) {

                    if (cachedResponse) {

                        return cachedResponse;

                    }


                    return fetch(
                        event.request
                    )

                        .then(
                            function (networkResponse) {

                                if (
                                    !networkResponse ||
                                    networkResponse.status !== 200 ||
                                    networkResponse.type !== "basic"
                                ) {

                                    return networkResponse;

                                }


                                const responseClone =
                                    networkResponse.clone();


                                caches.open(
                                    CACHE_NAME
                                )

                                    .then(
                                        function (cache) {

                                            cache.put(
                                                event.request,
                                                responseClone
                                            );

                                        }
                                    );


                                return networkResponse;

                            }
                        )

                        .catch(
                            function () {

                                return caches.match(
                                    "/food_process_system/offline.html"
                                );

                            }
                        );

                })

        );

    }
);