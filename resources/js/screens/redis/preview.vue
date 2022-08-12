<script type="text/ecmascript-6">
    export default {
        data(){
            return {
                entry: null,
                batch: [],
            };
        },
    }
</script>

<template>
    <preview-screen :title="__('Redis Command Details')" resource="redis" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">
                    {{__('Connection')}}
                </td>
                <td>
                    {{slotProps.entry.content.connection}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">
                    {{__('Duration')}}
                </td>
                <td>
                    {{__(':timems', {time: slotProps.entry.content.time})}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5">
                <div class="card-header">
                    <h5>{{__('Command')}}</h5>
                </div>

                <pre class="code-bg p-4 mb-0 text-white">{{slotProps.entry.content.command}}</pre>
            </div>
        </div>
    </preview-screen>
</template>
