<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import Layout from '@/Layouts/main.vue';
import PageHeader from '@/Components/page-header.vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import getChartColorsArray from '@/common/getChartColorsArray';

const { t } = useI18n();

const props = defineProps({
  serviceLocations: { type: Array, default: () => [] },
});

const selectedLocation = ref('all');
const loading = ref(true);
const currencySymbol = ref('');

const tripsPerDay = ref({ labels: [], values: [] });
const tripsChartOptions = ref({});

const revenuePerZone = ref({ labels: [], values: [] });
const revenueChartOptions = ref({});

const cancellationReasons = ref({ labels: [], values: [] });
const cancellationChartOptions = ref({});

async function fetchAnalytics() {
  loading.value = true;
  try {
    const params = {};
    if (selectedLocation.value && selectedLocation.value !== 'all') {
      params.service_location_id = selectedLocation.value;
    } else {
      params.service_location_id = 'all';
    }
    const { data } = await axios.get('/new-dashboard/analytics', { params });
    currencySymbol.value = data.currency_symbol || '';

    tripsPerDay.value = data.trips_per_day || { labels: [], values: [] };
    tripsChartOptions.value = {
      chart: { type: 'area', height: 320, toolbar: { show: false } },
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 2 },
      xaxis: { categories: tripsPerDay.value.labels },
      yaxis: { min: 0, tickAmount: 5 },
      colors: getChartColorsArray('["--vz-primary"]'),
      fill: { type: 'gradient', opacity: 0.2 },
    };

    revenuePerZone.value = data.revenue_per_zone || { labels: [], values: [] };
    revenueChartOptions.value = {
      chart: { type: 'bar', height: 320, toolbar: { show: false } },
      plotOptions: { bar: { horizontal: true, columnWidth: '55%', borderRadius: 4 } },
      dataLabels: { enabled: true, formatter: (v) => (currencySymbol.value ? currencySymbol.value + Number(v).toFixed(0) : Number(v).toFixed(0)) },
      xaxis: { categories: revenuePerZone.value.labels },
      colors: getChartColorsArray('["--vz-success"]'),
    };

    cancellationReasons.value = data.cancellation_reasons || { labels: [], values: [] };
    cancellationChartOptions.value = {
      chart: { type: 'donut', height: 320 },
      labels: cancellationReasons.value.labels,
      plotOptions: { pie: { donut: { size: '70%' } } },
      legend: { position: 'bottom' },
      colors: getChartColorsArray('["--vz-danger", "--vz-warning", "--vz-info", "--vz-secondary"]'),
    };
  } catch (err) {
    console.error('New dashboard analytics error', err);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  fetchAnalytics();
});

watch(selectedLocation, () => {
  fetchAnalytics();
});

const tripsSeries = ref([{ name: 'Trips', data: [] }]);
watch(tripsPerDay, (v) => {
  tripsSeries.value = [{ name: 'Trips', data: v.values || [] }];
}, { immediate: true });
</script>

<template>
  <Layout>
    <Head :title="t('new_dashboard')" />
    <PageHeader :title="t('new_dashboard')" :pageTitle="t('new_dashboard')" />

    <BRow class="mb-3">
      <BCol cols="12" md="4">
        <label class="form-label">{{ t('service_locations') }}</label>
        <select v-model="selectedLocation" class="form-select">
          <option value="all">{{ t('all') }}</option>
          <option v-for="s in serviceLocations" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </BCol>
    </BRow>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2 text-muted">{{ t('no_data_found') }}</p>
    </div>

    <template v-else>
      <BRow>
        <BCol xl="12">
          <BCard no-body class="card-height-100">
            <BCardHeader class="align-items-center d-flex py-0">
              <BCardTitle class="mb-0 flex-grow-1 p-3">{{ t('trips_per_day') }}</BCardTitle>
            </BCardHeader>
            <BCardBody>
              <apexchart
                v-if="tripsPerDay.values && tripsPerDay.values.length"
                class="apex-charts"
                dir="ltr"
                height="320"
                type="area"
                :options="tripsChartOptions"
                :series="tripsSeries"
              />
              <p v-else class="text-muted mb-0">{{ t('no_data_found') }}</p>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>

      <BRow>
        <BCol xl="6">
          <BCard no-body class="card-height-100">
            <BCardHeader class="align-items-center d-flex py-0">
              <BCardTitle class="mb-0 flex-grow-1 p-3">{{ t('revenue_per_zone') }}</BCardTitle>
            </BCardHeader>
            <BCardBody>
              <apexchart
                v-if="revenuePerZone.values && revenuePerZone.values.length"
                class="apex-charts"
                dir="ltr"
                height="320"
                type="bar"
                :options="revenueChartOptions"
                :series="[{ name: 'Revenue', data: revenuePerZone.values }]"
              />
              <p v-else class="text-muted mb-0">{{ t('no_data_found') }}</p>
            </BCardBody>
          </BCard>
        </BCol>
        <BCol xl="6">
          <BCard no-body class="card-height-100">
            <BCardHeader class="align-items-center d-flex py-0">
              <BCardTitle class="mb-0 flex-grow-1 p-3">{{ t('cancellation_reasons') }}</BCardTitle>
            </BCardHeader>
            <BCardBody>
              <apexchart
                v-if="cancellationReasons.values && cancellationReasons.values.some(v => v > 0)"
                class="apex-charts"
                dir="ltr"
                height="320"
                type="donut"
                :options="cancellationChartOptions"
                :series="cancellationReasons.values"
              />
              <p v-else class="text-muted mb-0">{{ t('no_data_found') }}</p>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>
    </template>
  </Layout>
</template>
