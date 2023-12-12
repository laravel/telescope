<script type="text/ecmascript-6">
    export default {
        data() {
            return {
                entry: null,
                batch: [],
                currentRequestTab: 'payload',
                currentResponseTab: 'response',
            };
        }
    }
</script>

<template>
    <preview-screen title="HTTP Client Request Details" resource="client-requests" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
        <tr>
            <td class="table-fit font-weight-bold">Method</td>
            <td>
                {{slotProps.entry.content.method}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">URI</td>
            <td>
                {{slotProps.entry.content.uri}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Status</td>
            <td>
                {{slotProps.entry.content.response_status !== undefined ? slotProps.entry.content.response_status : 'N/A'}}
            </td>
        </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentRequestTab=='payload'}" href="#" v-on:click.prevent="currentRequestTab='payload'">Payload</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentRequestTab=='headers'}" href="#" v-on:click.prevent="currentRequestTab='headers'">Headers</a>
                    </li>
                </ul>
                <div class="code-bg p-4 mb-0 text-white">
                    <vue-json-pretty :data="slotProps.entry.content.payload" v-if="currentRequestTab=='payload'"></vue-json-pretty>
                    <vue-json-pretty :data="slotProps.entry.content.headers" v-if="currentRequestTab=='headers'"></vue-json-pretty>
                </div>
            </div>
            <div class="card mt-5" v-if="slotProps.entry.content.response_status">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentResponseTab=='response'}" href="#" v-on:click.prevent="currentResponseTab='response'">Response</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentResponseTab=='headers'}" href="#" v-on:click.prevent="currentResponseTab='headers'">Headers</a>
                    </li>
                </ul>
                <div class="code-bg p-4 mb-0 text-white">
                    <vue-json-pretty :data="slotProps.entry.content.response" v-if="currentResponseTab=='response'"></vue-json-pretty>
                    <vue-json-pretty :data="slotProps.entry.content.response_headers" v-if="currentResponseTab=='headers'"></vue-json-pretty>
                </div>
            </div>
        </div>
    </preview-screen>
</template>
