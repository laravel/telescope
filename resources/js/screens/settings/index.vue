<script type="text/ecmascript-6">
    import $ from 'jquery';
    import axios from 'axios';

    export default {
        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
            };
        },

        /**
         * Prepare the component.
         */
        mounted(){
            document.title = "Settings - Telescope";

            axios.get('/telescope/telescope-api/monitored-tags').then(response => {
                this.tags = response.data.tags;

                this.ready = true;
            })
        },


        methods: {
            store(){

            }
        }
    }
</script>

<template>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5>Settings</h5>

            <button class="btn btn-primary" v-on:click.prevent="store">Store</button>
        </div>

        
    </div>
</template>
