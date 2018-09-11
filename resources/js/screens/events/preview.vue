<script type="text/ecmascript-6">
    export default {
        data(){
            return {
                entry: null,
                batch: [],
            };
        }
    }
</script>

<template>
    <preview-screen title="Event Details" resource="events" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Event</td>
                <td>
                    {{slotProps.entry.content.name}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <!-- Event Payload -->
            <div class="card mt-5" v-if="slotProps.entry.content.payload">
                <div class="card-header"><h5>Event Data</h5></div>

                <pre class="bg-dark p-4 mb-0 text-white"><tree-view :data="slotProps.entry.content.payload" :options="{maxDepth: 3}"></tree-view></pre>
            </div>

            <!-- Event Listeners -->
            <div class="card mt-5" v-if="slotProps.entry.content.listeners && slotProps.entry.content.listeners.length">
                <div class="card-header"><h5>Registered Listeners</h5></div>

                <table class="table mb-0">
                    <tbody>
                    <tr v-for="listener in slotProps.entry.content.listeners">
                        <td class="bg-secondary">{{listener}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </preview-screen>
</template>
