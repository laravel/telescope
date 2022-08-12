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
    <preview-screen :title="__('Scheduled Command Details')" resource="requests" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">
                    {{__('Description')}}
                </td>
                <td>
                    {{slotProps.entry.content.description || '-'}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">
                    {{__('Command')}}
                </td>
                <td>
                    {{slotProps.entry.content.command || '-'}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">
                    {{__('Expression')}}
                </td>
                <td>
                    {{slotProps.entry.content.expression}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">
                    {{__('User')}}
                </td>
                <td>
                    {{slotProps.entry.content.user || '-'}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">
                    {{__('Timezone')}}
                </td>
                <td>
                    {{slotProps.entry.content.timezone || '-'}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps" v-if="slotProps.entry.content.output">
            <div class="card mt-5">
                <div class="card-header">
                    <h5>{{__('Output')}}</h5>
                </div>

                <pre class="code-bg p-4 mb-0 text-white">{{ slotProps.entry.content.output }}</pre>
            </div>
        </div>
    </preview-screen>
</template>
