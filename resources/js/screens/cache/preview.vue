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
        };
    },

    methods: {
        formatExpiration(expiration) {
            return expiration + ' seconds';
        }
    }
}
</script>

<template>
    <preview-screen title="Cache Details" resource="cache" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">Action</td>
                <td>
                    <span class="badge" :class="'badge-' + cacheActionTypeClass(slotProps.entry.content.type)">
                        {{ slotProps.entry.content.type }}
                    </span>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Key</td>
                <td>
                    {{ slotProps.entry.content.key }}
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.expiration">
                <td class="table-fit text-muted">Expiration</td>
                <td>
                    {{ formatExpiration(slotProps.entry.content.expiration) }}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5 overflow-hidden" v-if="slotProps.entry.content.value">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link active">Value</a>
                    </li>
                </ul>

                <pre class="code-bg p-4 mb-0 text-white">{{ slotProps.entry.content.value }}</pre>
            </div>
        </div>
    </preview-screen>
</template>
