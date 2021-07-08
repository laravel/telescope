<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta Information -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('/vendor/telescope/favicon.ico') }}">

    <meta name="robots" content="noindex, nofollow">

    <title>Telescope{{ config('app.name') ? ' - ' . config('app.name') : '' }}</title>

    <!-- Style sheets-->
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link href="{{ asset(mix($cssFile, 'vendor/telescope')) }}" rel="stylesheet" type="text/css">
</head>
<body>
<div id="telescope" v-cloak>
    <alert :message="alert.message"
           :type="alert.type"
           :auto-close="alert.autoClose"
           :confirmation-proceed="alert.confirmationProceed"
           :confirmation-cancel="alert.confirmationCancel"
           v-if="alert.type"></alert>

    <div class="container mb-5">
        <div class="d-flex align-items-center py-4 header">
            <svg class="logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80">
                <path class="fill-primary" d="M0 40a39.87 39.87 0 0 1 11.72-28.28A40 40 0 1 1 0 40zm34 10a4 4 0 0 1-4-4v-2a2 2 0 1 0-4 0v2a4 4 0 0 1-4 4h-2a2 2 0 1 0 0 4h2a4 4 0 0 1 4 4v2a2 2 0 1 0 4 0v-2a4 4 0 0 1 4-4h2a2 2 0 1 0 0-4h-2zm24-24a6 6 0 0 1-6-6v-3a3 3 0 0 0-6 0v3a6 6 0 0 1-6 6h-3a3 3 0 0 0 0 6h3a6 6 0 0 1 6 6v3a3 3 0 0 0 6 0v-3a6 6 0 0 1 6-6h3a3 3 0 0 0 0-6h-3zm-4 36a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM21 28a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"></path>
            </svg>

            <h4 class="mb-0 ml-3"><strong>Laravel</strong> Telescope{{ config('app.name') ? ' - ' . config('app.name') : '' }}</h4>

            <button class="btn btn-outline-primary ml-auto mr-3" v-on:click.prevent="toggleRecording" title="Play/Pause">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary" v-if="recording">
                    <path d="M5 4h3v12H5V4zm7 0h3v12h-3V4z"/>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary" v-else>
                    <path d="M4 4l12 6-12 6z"/>
                </svg>
            </button>

            <button class="btn btn-outline-primary mr-3" v-on:click.prevent="clearEntries" title="Clear Entries">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary">
                    <path d="M6 2l2-2h4l2 2h4v2H2V2h4zM3 6h14l-1 14H4L3 6zm5 2v10h1V8H8zm3 0v10h1V8h-1z"/>
                </svg>
            </button>

            <button class="btn btn-outline-primary mr-3" :class="{active: autoLoadsNewEntries}" v-on:click.prevent="autoLoadNewEntries" title="Auto Load Entries">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary">
                    <path d="M10 3v2a5 5 0 0 0-3.54 8.54l-1.41 1.41A7 7 0 0 1 10 3zm4.95 2.05A7 7 0 0 1 10 17v-2a5 5 0 0 0 3.54-8.54l1.41-1.41zM10 20l-4-4 4-4v8zm0-12V0l4 4-4 4z"></path>
                </svg>
            </button>

            <div class="btn-group" role="group" aria-label="Basic example">
                <router-link tag="button" to="/monitored-tags" class="btn btn-outline-primary" active-class="active" title="Monitoring">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-primary">
                        <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                    </svg>
                </router-link>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-2 sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <router-link active-class="active" to="/requests" class="nav-link d-flex align-items-center pt-0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M0 3c0-1.1.9-2 2-2h16a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3zm2 2v12h16V5H2zm8 3l4 5H6l4-5z"></path>
                            </svg>
                            <span>Requests</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/commands" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M7 17H2a2 2 0 0 1-2-2V2C0 .9.9 0 2 0h16a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2h-5l4 2v1H3v-1l4-2zM2 2v11h16V2H2z"></path>
                            </svg>
                            <span>Commands</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/schedule" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M10 20a10 10 0 1 1 0-20 10 10 0 0 1 0 20zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm-1-7.59V4h2v5.59l3.95 3.95-1.41 1.41L9 10.41z"></path>
                            </svg>
                            <span>Schedule</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/jobs" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M0 2h20v4H0V2zm0 8h20v2H0v-2zm0 6h20v2H0v-2z"></path>
                            </svg>
                            <span>Jobs</span>
                        </router-link>
                    </li>


                    <li class="nav-item mt-3">
                        <router-link active-class="active" to="/batches" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M1 1h18v2H1V1zm0 8h18v2H1V9zm0 8h18v2H1v-2zM1 5h18v2H1V5zm0 8h18v2H1v-2z"/>
                            </svg>
                            <span>Batches</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/cache" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M16 2h4v15a3 3 0 0 1-3 3H3a3 3 0 0 1-3-3V0h16v2zm0 2v13a1 1 0 0 0 1 1 1 1 0 0 0 1-1V4h-2zM2 2v15a1 1 0 0 0 1 1h11.17a2.98 2.98 0 0 1-.17-1V2H2zm2 8h8v2H4v-2zm0 4h8v2H4v-2zM4 4h8v4H4V4z"></path>
                            </svg>
                            <span>Cache</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/dumps" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M.7 9.3l4.8-4.8 1.4 1.42L2.84 10l4.07 4.07-1.41 1.42L0 10l.7-.7zm18.6 1.4l.7-.7-5.49-5.49-1.4 1.42L17.16 10l-4.07 4.07 1.41 1.42 4.78-4.78z"></path>
                            </svg>
                            <span>Dumps</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/events" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M16 8A6 6 0 1 0 4 8v11H2a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2V8a8 8 0 1 1 16 0v3a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2V8zm-4 2h3v10h-3V10zm-7 0h3v10H5V10z"></path>
                            </svg>
                            <span>Events</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/exceptions" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M15.3 14.89l2.77 2.77a1 1 0 0 1 0 1.41 1 1 0 0 1-1.41 0l-2.59-2.58A5.99 5.99 0 0 1 11 18V9.04a1 1 0 0 0-2 0V18a5.98 5.98 0 0 1-3.07-1.51l-2.59 2.58a1 1 0 0 1-1.41 0 1 1 0 0 1 0-1.41l2.77-2.77A5.95 5.95 0 0 1 4.07 13H1a1 1 0 1 1 0-2h3V8.41L.93 5.34a1 1 0 0 1 0-1.41 1 1 0 0 1 1.41 0l2.1 2.1h11.12l2.1-2.1a1 1 0 0 1 1.41 0 1 1 0 0 1 0 1.41L16 8.41V11h3a1 1 0 1 1 0 2h-3.07c-.1.67-.32 1.31-.63 1.89zM15 5H5a5 5 0 1 1 10 0z"></path>
                            </svg>
                            <span>Exceptions</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/gates" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M7 10V7a5 5 0 1 1 10 0v3h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-8c0-1.1.9-2 2-2h2zm2 0h6V7a3 3 0 0 0-6 0v3zm-4 2v8h14v-8H5zm7 2a1 1 0 0 1 1 1v2a1 1 0 0 1-2 0v-2a1 1 0 0 1 1-1z"></path>
                            </svg>
                            <span>Gates</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/client-requests" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M10 20a10 10 0 1 1 0-20 10 10 0 0 1 0 20zm2-2.25a8 8 0 0 0 4-2.46V9a2 2 0 0 1-2-2V3.07a7.95 7.95 0 0 0-3-1V3a2 2 0 0 1-2 2v1a2 2 0 0 1-2 2v2h3a2 2 0 0 1 2 2v5.75zm-4 0V15a2 2 0 0 1-2-2v-1h-.5A1.5 1.5 0 0 1 4 10.5V8H2.25A8.01 8.01 0 0 0 8 17.75z"/>
                            </svg>
                            <span>Http Client</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/logs" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M0 2C0 .9.9 0 2 0h16a2 2 0 0 1 2 2v2H0V2zm1 3h18v13a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V5zm6 2v2h6V7H7z"></path>
                            </svg>
                            <span>Logs</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/mail" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M18 2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4c0-1.1.9-2 2-2h16zm-4.37 9.1L20 16v-2l-5.12-3.9L20 6V4l-10 8L0 4v2l5.12 4.1L0 14v2l6.37-4.9L10 14l3.63-2.9z"></path>
                            </svg>
                            <span>Mail</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/models" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M10 1l10 6-10 6L0 7l10-6zm6.67 10L20 13l-10 6-10-6 3.33-2L10 15l6.67-4z"></path>
                            </svg>
                            <span>Models</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/notifications" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M3 6c0-1.1.9-2 2-2h8l4-4h2v16h-2l-4-4H5a2 2 0 0 1-2-2H1V6h2zm8 9v5H8l-1.67-5H5v-2h8v2h-2z"></path>
                            </svg>
                            <span>Notifications</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/queries" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M1 1h18v2H1V1zm0 8h18v2H1V9zm0 8h18v2H1v-2zM1 5h12v2H1V5zm0 8h12v2H1v-2z"></path>
                            </svg>
                            <span>Queries</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/redis" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M.96 9.84c1.35.61 6.83 2.83 7.73 3.26.93.44 1.58.45 2.75-.16 1.13-.59 6.21-2.68 7.6-3.36l.16.06c1.04.38 1.08.7.01 1.25-1.06.56-6.59 2.83-7.77 3.44-1.17.62-1.82.61-2.75.17-.93-.45-6.81-2.82-7.87-3.33-1.06-.5-1.08-.85-.04-1.26l.18-.07zM.8 13.19h.01c1.06.5 6.94 2.88 7.87 3.33.93.44 1.58.45 2.75-.17 1.17-.6 6.6-2.84 7.74-3.42h.02c1.04.39 1.08.7.01 1.26-1.06.55-6.59 2.82-7.77 3.44-1.17.61-1.82.6-2.75.16-.93-.44-6.81-2.82-7.87-3.33-1.06-.5-1.08-.85-.04-1.26l.03-.01zm18.4-5.71c-1.06.55-6.59 2.82-7.77 3.44-1.17.61-1.82.6-2.75.16-.93-.44-6.81-2.82-7.87-3.32C-.24 7.25-.26 6.9.78 6.49c1.04-.4 6.89-2.7 8.12-3.14 1.24-.44 1.67-.46 2.72-.07 1.05.38 6.54 2.57 7.58 2.95 1.04.38 1.08.7.01 1.25zm-6.59-1.95l-1.34-.5.36-.86-1.32.44-1.4-.55.45.83-1.5.53 2 .18.63 1.04.39-.93 1.73-.18zm-2.22 4.53L11.8 8l-4.63.7 3.23 1.35zm-4.48-2.1c1.37 0 2.47-.42 2.47-.95s-1.1-.96-2.47-.96-2.48.43-2.48.96 1.11.96 2.48.96zm8.75-2.17v2.16l2.74-1.08-2.74-1.08zm-3.03 1.2l2.73 1.08.3-.12V5.8l-3.03 1.2z"></path>
                            </svg>
                            <span>Redis</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/views" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M7 3H2v14h5V3zm2 0v14h9V3H9zM0 3c0-1.1.9-2 2-2h16a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3zm3 1h3v2H3V4zm0 3h3v2H3V7zm0 3h3v2H3v-2z"/>
                            </svg>
                            <span>Views</span>
                        </router-link>
                    </li>
                </ul>
            </div>

            <div class="col-10">
                <router-view></router-view>
            </div>
        </div>
    </div>
</div>

<!-- Global Telescope Object -->
<script>
    window.Telescope = @json($telescopeScriptVariables);
</script>

<script src="{{ asset(mix('app.js', 'vendor/telescope')) }}"></script>
</body>
</html>
