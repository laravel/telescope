<script type="text/ecmascript-6">
    export default {
        data(){
            return {
                entry: null,
                batch: [],
                currentTab: 'data'
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

                    <span class="badge badge-secondary font-weight-light ml-2" v-if="slotProps.entry.content.broadcast">
                        Broadcast
                    </span>
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='data'}" href="#" v-on:click.prevent="currentTab='data'">Event Data</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='listeners'}" href="#" v-on:click.prevent="currentTab='listeners'">Listeners</a>
                    </li>
                </ul>
                <div>
                    <!-- Event Payload -->
                    <div class="code-bg p-4 mb-0 text-white" v-show="currentTab=='data'">
                        <vue-json-pretty :data="slotProps.entry.content.payload"></vue-json-pretty>
                    </div>

                    <!-- Event Listeners -->
                    <table class="table mb-0" v-show="currentTab=='listeners'">
                        <tbody>
                        <tr v-for="listener in slotProps.entry.content.listeners">
                            <td class="card-bg-secondary">
                                {{ listener.name }}

                                <span class="badge badge-secondary font-weight-light ml-2" v-if="listener.queued">
                                    Queued
                                </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </preview-screen>
</template>
