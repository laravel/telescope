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
                if (expiration < 1) {
                    return expiration * 60 + ' seconds';
                }

                return expiration + ' minutes';
            }
        }
    }
</script>

<template>
    <preview-screen title="Cache Details" resource="cache" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Action</td>
                <td>
                    <span class="badge font-weight-light" :class="'badge-'+cacheActionTypeClass(slotProps.entry.content.type)">
                        {{slotProps.entry.content.type}}
                    </span>
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Key</td>
                <td>
                    {{slotProps.entry.content.key}}
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.expiration">
                <td class="table-fit font-weight-bold">Expiration</td>
                <td>
                    {{formatExpiration(slotProps.entry.content.expiration)}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5" v-if="slotProps.entry.content.value">
                <div class="card-header"><h5>Value</h5></div>

                <pre class="code-bg p-4 mb-0 text-white">{{slotProps.entry.content.value}}</pre>
            </div>
        </div>
    </preview-screen>
</template>
