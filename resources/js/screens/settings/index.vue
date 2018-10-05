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
                nightMode: localStorage.nightMode === 'true',
                autoLoadEntries: localStorage.autoLoadEntries  === 'true',
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

        watch: {
            nightMode(v){
                localStorage.nightMode = v;

                location.reload();
            },

            autoLoadEntries(v){
                localStorage.autoLoadEntries = v;
            }
        }
    }
</script>

<template>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5>Settings</h5>
        </div>

        <div class="card-body card-bg-secondary">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" v-model="nightMode" id="nightMode">
                <label class="form-check-label" for="nightMode">
                    Night Mode
                </label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="checkbox" v-model="autoLoadEntries" id="autoLoadEntries">
                <label class="form-check-label" for="autoLoadEntries">
                    Auto-load new Entries
                </label>
            </div>
        </div>
    </div>
</template>
