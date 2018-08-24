<script type="text/ecmascript-6">
    import _ from 'lodash';
    import axios from 'axios';
    import $ from 'jquery';

    export default {
        components: {},


        /**
         * The component's data.
         */
        data() {
            return {
                entry: null,
                ready: false,
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Notifications - Telescope";

            axios.get('/telescope/telescope-api/notifications/' + this.$route.params.id).then(response => {
                this.entry = response.data.entry;

                this.ready = true;
            }).catch(error => {
                this.ready = true;
            })
        },
    }
</script>

<template>
    <loader :loading="!ready">
        <div v-if="!entry">No entry found.</div>

        <div v-else>
            <table class="table table-sm">
                <tr>
                    <td class="font-weight-bold pl-0">Time</td>
                    <td>{{entry.created_at}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Channel</td>
                    <td>{{entry.content.channel}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Notifiable</td>
                    <td>{{entry.content.notifiable}}</td>
                </tr>

                <tr>
                    <td class="font-weight-bold pl-0">Notification</td>
                    <td>{{entry.content.notification}}</td>
                </tr>
            </table>
        </div>
    </loader>
</template>

<style scoped>
</style>