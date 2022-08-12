<script type="text/ecmascript-6">
    export default {
        data() {
            return {
                entry: null,
                batch: [],
                currentTab: 'payload'
            };
        }
    }
</script>

<template>
    <preview-screen :title="__('Request Details')" resource="requests" :id="$route.params.id" entry-point="true">
        <template slot="table-parameters" slot-scope="slotProps">
        <tr>
            <td class="table-fit font-weight-bold">
                {{__('Method')}}
            </td>
            <td>
                {{slotProps.entry.content.method}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">
                {{__('Controller Action')}}
            </td>
            <td>
                {{slotProps.entry.content.controller_action}}
            </td>
        </tr>

        <tr v-if="slotProps.entry.content.middleware">
            <td class="table-fit font-weight-bold">
                {{__('Middleware')}}
            </td>
            <td>
                {{slotProps.entry.content.middleware.join(", ")}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">
                {{__('Path')}}
            </td>
            <td>
                {{slotProps.entry.content.uri}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">
                {{__('Status')}}
            </td>
            <td>
                {{slotProps.entry.content.response_status}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">
                {{__('Duration')}}
            </td>
            <td>
                {{__(':time ms', {time: slotProps.entry.content.duration || '-'})}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">IP Address</td>
            <td>
                {{slotProps.entry.content.ip_address || '-'}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Memory usage</td>
            <td>
                {{__(':count MB', {count: slotProps.entry.content.memory || '-'})}}
            </td>
        </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='payload'}" href="#" v-on:click.prevent="currentTab='payload'">
                            {{__('Payload')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='headers'}" href="#" v-on:click.prevent="currentTab='headers'">
                            {{__('Headers')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='session'}" href="#" v-on:click.prevent="currentTab='session'">
                            {{__('Session')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='response'}" href="#" v-on:click.prevent="currentTab='response'">
                            {{__('Response')}}
                        </a>
                    </li>
                </ul>
                <div class="code-bg p-4 mb-0 text-white">
                    <vue-json-pretty :data="slotProps.entry.content.payload" v-if="currentTab=='payload'"></vue-json-pretty>
                    <vue-json-pretty :data="slotProps.entry.content.headers" v-if="currentTab=='headers'"></vue-json-pretty>
                    <vue-json-pretty :data="slotProps.entry.content.session" v-if="currentTab=='session'"></vue-json-pretty>
                    <vue-json-pretty :data="slotProps.entry.content.response" v-if="currentTab=='response'"></vue-json-pretty>
                </div>
            </div>

            <!-- Additional Information -->
            <related-entries :entry="entry" :batch="batch">
            </related-entries>
        </div>
    </preview-screen>
</template>
