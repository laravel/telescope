<script type="text/ecmascript-6">
    export default {}
</script>

<template>
    <index-screen title="Dumps" resource="dumps">
        <tr slot="table-header">
            <th scope="col">Dump</th>
        </tr>

        <template slot="row" slot-scope="slotProps">
            <td v-html="slotProps.entry.content.dump"></td>
        </template>
    </index-screen>
</template>
