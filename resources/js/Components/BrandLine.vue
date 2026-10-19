<script setup>
// Divisor con la composición del logo: ondas verdes a la izquierda, punto morado al centro, azules a la derecha.
</script>

<template>
    <div class="brand-line" aria-hidden="true">
        <span class="brand-line__side brand-line__side--green"></span>
        <span class="brand-line__dot"></span>
        <span class="brand-line__side brand-line__side--blue"></span>
    </div>
</template>

<style scoped>
.brand-line { display: flex; align-items: center; gap: 14px; height: 18px; background: var(--surface); padding-inline: 0; }
.brand-line__side { flex: 1; height: 6px; }
.brand-line__side--green { background: var(--logo-green); border-radius: 0 3px 3px 0; }
.brand-line__side--blue { background: var(--logo-blue); border-radius: 3px 0 0 3px; }
.brand-line__dot { width: 18px; height: 18px; border-radius: 50%; background: var(--logo-purple); flex-shrink: 0; }
</style>
