<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta Information -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Telescope</title>

    <!-- Style sheets-->
    <link href='{{asset('vendors/telescope/app.css')}}' rel='stylesheet' type='text/css'>

    <!-- Icons -->
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css' rel='stylesheet' type='text/css'>
</head>
<body>
<div id="telescope" v-cloak>
    <div class="container">
        <div class="row">
            <div class="col-3">
                <div class="list-group">
                    <router-link active-class="active" to="/overview" class="list-group-item list-group-item-action">Overview</router-link>
                    <router-link active-class="active" to="/mail" class="list-group-item list-group-item-action">Mail</router-link>
                    <router-link active-class="active" to="/log" class="list-group-item list-group-item-action">Log</router-link>
                    <router-link active-class="active" to="/notifications" class="list-group-item list-group-item-action">Notifications</router-link>
                    <router-link active-class="active" to="/queue" class="list-group-item list-group-item-action">Queue</router-link>
                    <router-link active-class="active" to="/events" class="list-group-item list-group-item-action">Events</router-link>
                    <router-link active-class="active" to="/cache" class="list-group-item list-group-item-action">Cache</router-link>
                    <router-link active-class="active" to="/queries" class="list-group-item list-group-item-action">Queries</router-link>
                    <router-link active-class="active" to="/requests" class="list-group-item list-group-item-action">Requests</router-link>
                </div>
            </div>

            <div class="col-9">
                <router-view></router-view>
            </div>
        </div>
    </div>
</div>

<script src="{{asset('vendors/telescope/app.js')}}"></script>
</body>
</html>