<script type="text/ecmascript-6">
import StylesMixin from './../../mixins/entriesStyles';

export default {
    mixins: [
        StylesMixin,
    ],

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
                <td class="table-fit text-muted">Method</td>
                <td>
                    <span class="badge" :class="'badge-' + requestMethodClass(slotProps.entry.content.method)">
                        {{ slotProps.entry.content.method }}
                    </span>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">URI</td>
                <td>
                    {{ slotProps.entry.content.uri }}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Status</td>
                <td>
                    <span
                        class="badge"
                        :class="
                            'badge-' +
                            requestStatusClass(
                                slotProps.entry.content.response_status !== undefined
                                    ? slotProps.entry.content.response_status
                                    : null
                            )
                        "
                    >
                        {{
                            slotProps.entry.content.response_status !== undefined
                                ? slotProps.entry.content.response_status
                                : 'N/A'
                        }}
                    </span>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Duration</td>
                <td>{{ slotProps.entry.content.duration || '-' }}ms</td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5 overflow-hidden">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{ active: currentRequestTab == 'payload' }"
                            href="#"
                            v-on:click.prevent="currentRequestTab = 'payload'"
                            >Payload</a
                        >
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{ active: currentRequestTab == 'headers' }"
                            href="#"
                            v-on:click.prevent="currentRequestTab = 'headers'"
                            >Headers</a
                        >
                    </li>
                </ul>
                <div class="code-bg p-4 mb-0 text-white">
                    <copy-clipboard :data="slotProps.entry.content[currentRequestTab]">
                        <vue-json-pretty :data="slotProps.entry.content[currentRequestTab]"></vue-json-pretty>
                    </copy-clipboard>
                </div>
            </div>
            <div class="card mt-5 overflow-hidden" v-if="slotProps.entry.content.response_status">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{
                                active: currentResponseTab == 'response',
                            }"
                            href="#"
                            v-on:click.prevent="currentResponseTab = 'response'"
                            >Response</a
                        >
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{ active: currentResponseTab == 'headers' }"
                            href="#"
                            v-on:click.prevent="currentResponseTab = 'headers'"
                            >Headers</a
                        >
                    </li>
                </ul>
                <div class="code-bg p-4 mb-0 text-white">
                    <template v-if="currentResponseTab == 'response'">
                        <copy-clipboard :data="slotProps.entry.content.response">
                            <vue-json-pretty :data="slotProps.entry.content.response"></vue-json-pretty>
                        </copy-clipboard>
                    </template>
                    <template v-if="currentResponseTab == 'headers'">
                        <copy-clipboard :data="slotProps.entry.content.response_headers">
                            <vue-json-pretty :data="slotProps.entry.content.response_headers"></vue-json-pretty>
                        </copy-clipboard>
                    </template>
                </div>
            </div>
        </div>
    </preview-screen>
</template>
