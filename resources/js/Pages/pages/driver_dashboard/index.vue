<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
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

const summary = ref({
  total: 0,
  approved: 0,
  pending: 0,
  active: 0,
  new_this_month: 0,
  documents_expiring_7_days: 0,
  documents_expiring_30_days: 0,
  negative_balance: 0,
});

const driversPerZone = ref({ labels: [], values: [] });
const driversPerZoneChartOptions = ref({});

const driversByVehicleType = ref({ labels: [], values: [] });
const driversByVehicleTypeChartOptions = ref({});

const registrationsOverTime = ref({ labels: [], values: [] });
const registrationsChartOptions = ref({});
const registrationsSeries = ref([{ name: 'Drivers', data: [] }]);

const acceptanceBuckets = ref({ labels: [], values: [] });
const acceptanceChartOptions = ref({});

const topByTrips = ref([]);
const topByEarnings = ref([]);

async function fetchAnalytics() {
  loading.value = true;
  try {
    const params = { service_location_id: selectedLocation.value || 'all' };
    const { data } = await axios.get('/driver-dashboard/analytics', { params });
    currencySymbol.value = data.currency_symbol || '';
    summary.value = data.summary || summary.value;

    driversPerZone.value = data.drivers_per_zone || { labels: [], values: [] };
    driversPerZoneChartOptions.value = {
      chart: { type: 'bar', height: 280, toolbar: { show: false } },
      plotOptions: { bar: { horizontal: true, columnWidth: '55%', borderRadius: 4 } },
      dataLabels: { enabled: true },
      xaxis: { categories: driversPerZone.value.labels },
      colors: getChartColorsArray('["--vz-primary"]'),
    };

    driversByVehicleType.value = data.drivers_by_vehicle_type || { labels: [], values: [] };
    driversByVehicleTypeChartOptions.value = {
      chart: { type: 'donut', height: 280 },
      labels: driversByVehicleType.value.labels,
      plotOptions: { pie: { donut: { size: '70%' } } },
      legend: { position: 'bottom' },
      colors: getChartColorsArray('["--vz-primary", "--vz-success", "--vz-warning", "--vz-info", "--vz-secondary"]'),
    };

    registrationsOverTime.value = data.registrations_over_time || { labels: [], values: [] };
    registrationsSeries.value = [{ name: t('driver_dashboard_registrations'), data: registrationsOverTime.value.values || [] }];
    registrationsChartOptions.value = {
      chart: { type: 'area', height: 280, toolbar: { show: false } },
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 2 },
      xaxis: { categories: registrationsOverTime.value.labels },
      yaxis: { min: 0, tickAmount: 5 },
      colors: getChartColorsArray('["--vz-success"]'),
      fill: { type: 'gradient', opacity: 0.2 },
    };

    acceptanceBuckets.value = data.acceptance_ratio_buckets || { labels: [], values: [] };
    acceptanceChartOptions.value = {
      chart: { type: 'bar', height: 280, toolbar: { show: false } },
      plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
      dataLabels: { enabled: true },
      xaxis: { categories: acceptanceBuckets.value.labels },
      colors: getChartColorsArray('["--vz-info"]'),
    };

    topByTrips.value = data.top_drivers_by_trips || [];
    topByEarnings.value = data.top_drivers_by_earnings || [];
  } catch (err) {
    console.error('Driver dashboard analytics error', err);
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

function exportReport(format) {
  const params = new URLSearchParams({ format, service_location_id: selectedLocation.value || 'all' });
  window.location.href = `/driver-dashboard/export?${params.toString()}`;
}
</script>

<template>
  <Layout>
    <Head :title="t('driver_dashboard')" />
    <PageHeader :title="t('driver_dashboard')" :pageTitle="t('driver_dashboard')" />

    <BRow class="mb-3 align-items-end">
      <BCol cols="12" md="4">
        <label class="form-label">{{ t('service_locations') }}</label>
        <select v-model="selectedLocation" class="form-select">
          <option value="all">{{ t('all') }}</option>
          <option v-for="s in serviceLocations" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </BCol>
      <BCol cols="12" md="4" class="mt-2 mt-md-0">
        <label class="form-label d-block">&nbsp;</label>
        <div class="btn-group">
          <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">{{ t('export') || 'Export' }}</button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#" @click.prevent="exportReport('xlsx')">{{ t('excel') || 'Excel' }}</a></li>
            <li><a class="dropdown-item" href="#" @click.prevent="exportReport('pdf')">PDF</a></li>
          </ul>
        </div>
      </BCol>
    </BRow>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2 text-muted">{{ t('loading') || 'Loading...' }}</p>
    </div>

    <template v-else>
      <!-- Summary cards -->
      <BRow>
        <BCol xl="2" md="4" sm="6">
          <BCard no-body class="card-animate">
            <BCardBody>
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">{{ t('total_drivers') }}</p>
              <h4 class="fs-22 fw-semibold ff-secondary mt-2">{{ summary.total }}</h4>
            </BCardBody>
          </BCard>
        </BCol>
        <BCol xl="2" md="4" sm="6">
          <BCard no-body class="card-animate">
            <BCardBody>
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">{{ t('approved_drivers') }}</p>
              <h4 class="fs-22 fw-semibold ff-secondary mt-2">{{ summary.approved }}</h4>
              <Link href="/approved-drivers" class="text-decoration-underline small">{{ t('view_all') }}</Link>
            </BCardBody>
          </BCard>
        </BCol>
        <BCol xl="2" md="4" sm="6">
          <BCard no-body class="card-animate">
            <BCardBody>
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">{{ t('pending_approval') }}</p>
              <h4 class="fs-22 fw-semibold ff-secondary mt-2">{{ summary.pending }}</h4>
              <Link href="/pending-drivers" class="text-decoration-underline small">{{ t('view_all') }}</Link>
            </BCardBody>
          </BCard>
        </BCol>
        <BCol xl="2" md="4" sm="6">
          <BCard no-body class="card-animate">
            <BCardBody>
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">{{ t('active') }}</p>
              <h4 class="fs-22 fw-semibold ff-secondary mt-2">{{ summary.active }}</h4>
            </BCardBody>
          </BCard>
        </BCol>
        <BCol xl="2" md="4" sm="6">
          <BCard no-body class="card-animate">
            <BCardBody>
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">{{ t('new_this_month') }}</p>
              <h4 class="fs-22 fw-semibold ff-secondary mt-2">{{ summary.new_this_month }}</h4>
            </BCardBody>
          </BCard>
        </BCol>
        <BCol xl="2" md="4" sm="6">
          <BCard no-body class="card-animate">
            <BCardBody>
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">{{ t('driver_dashboard_docs_expiring_7') }}</p>
              <h4 class="fs-22 fw-semibold ff-secondary mt-2">{{ summary.documents_expiring_7_days ?? 0 }}</h4>
            </BCardBody>
          </BCard>
        </BCol>
        <BCol xl="2" md="4" sm="6">
          <BCard no-body class="card-animate">
            <BCardBody>
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">{{ t('driver_dashboard_docs_expiring_30') }}</p>
              <h4 class="fs-22 fw-semibold ff-secondary mt-2">{{ summary.documents_expiring_30_days ?? 0 }}</h4>
            </BCardBody>
          </BCard>
        </BCol>
        <BCol xl="2" md="4" sm="6">
          <BCard no-body class="card-animate">
            <BCardBody>
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">{{ t('driver_dashboard_negative_balance') }}</p>
              <h4 class="fs-22 fw-semibold ff-secondary mt-2">{{ summary.negative_balance ?? 0 }}</h4>
              <Link href="/negative-balance-drivers" class="text-decoration-underline small">{{ t('view_all') }}</Link>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>

      <!-- Drivers per zone -->
      <BRow>
        <BCol xl="6">
          <BCard no-body class="card-height-100">
            <BCardHeader class="align-items-center d-flex py-0">
              <BCardTitle class="mb-0 flex-grow-1 p-3">{{ t('driver_dashboard_per_zone') }}</BCardTitle>
            </BCardHeader>
            <BCardBody>
              <apexchart
                v-if="driversPerZone.values && driversPerZone.values.length"
                class="apex-charts"
                dir="ltr"
                height="280"
                type="bar"
                :options="driversPerZoneChartOptions"
                :series="[{ name: t('drivers'), data: driversPerZone.values }]"
              />
              <p v-else class="text-muted mb-0">{{ t('no_data_found') }}</p>
            </BCardBody>
          </BCard>
        </BCol>
        <BCol xl="6">
          <BCard no-body class="card-height-100">
            <BCardHeader class="align-items-center d-flex py-0">
              <BCardTitle class="mb-0 flex-grow-1 p-3">{{ t('driver_dashboard_by_vehicle_type') }}</BCardTitle>
            </BCardHeader>
            <BCardBody>
              <apexchart
                v-if="driversByVehicleType.values && driversByVehicleType.values.some(v => v > 0)"
                class="apex-charts"
                dir="ltr"
                height="280"
                type="donut"
                :options="driversByVehicleTypeChartOptions"
                :series="driversByVehicleType.values"
              />
              <p v-else class="text-muted mb-0">{{ t('no_data_found') }}</p>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>

      <!-- Registrations over time + Acceptance ratio -->
      <BRow>
        <BCol xl="6">
          <BCard no-body class="card-height-100">
            <BCardHeader class="align-items-center d-flex py-0">
              <BCardTitle class="mb-0 flex-grow-1 p-3">{{ t('driver_dashboard_registrations_over_time') }}</BCardTitle>
            </BCardHeader>
            <BCardBody>
              <apexchart
                v-if="registrationsOverTime.values && registrationsOverTime.values.length"
                class="apex-charts"
                dir="ltr"
                height="280"
                type="area"
                :options="registrationsChartOptions"
                :series="registrationsSeries"
              />
              <p v-else class="text-muted mb-0">{{ t('no_data_found') }}</p>
            </BCardBody>
          </BCard>
        </BCol>
        <BCol xl="6">
          <BCard no-body class="card-height-100">
            <BCardHeader class="align-items-center d-flex py-0">
              <BCardTitle class="mb-0 flex-grow-1 p-3">{{ t('driver_dashboard_acceptance_ratio') }}</BCardTitle>
            </BCardHeader>
            <BCardBody>
              <apexchart
                v-if="acceptanceBuckets.values && acceptanceBuckets.values.some(v => v > 0)"
                class="apex-charts"
                dir="ltr"
                height="280"
                type="bar"
                :options="acceptanceChartOptions"
                :series="[{ name: t('drivers'), data: acceptanceBuckets.values }]"
              />
              <p v-else class="text-muted mb-0">{{ t('no_data_found') }}</p>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>

      <!-- Top drivers tables -->
      <BRow>
        <BCol xl="6">
          <BCard no-body>
            <BCardHeader class="align-items-center d-flex py-0">
              <BCardTitle class="mb-0 flex-grow-1 p-3">{{ t('driver_dashboard_top_by_trips') }}</BCardTitle>
            </BCardHeader>
            <BCardBody>
              <div class="table-responsive">
                <table class="table table-sm table-centered align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>{{ t('name') }}</th>
                      <th class="text-end">{{ t('driver_dashboard_trips') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in topByTrips" :key="row.id">
                      <td><Link :href="`/approved-drivers/view-profile/${row.id}`">{{ row.name }}</Link></td>
                      <td class="text-end">{{ row.trip_count }}</td>
                    </tr>
                    <tr v-if="!topByTrips.length">
                      <td colspan="2" class="text-muted text-center">{{ t('no_data_found') }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </BCardBody>
          </BCard>
        </BCol>
        <BCol xl="6">
          <BCard no-body>
            <BCardHeader class="align-items-center d-flex py-0">
              <BCardTitle class="mb-0 flex-grow-1 p-3">{{ t('driver_dashboard_top_by_earnings') }}</BCardTitle>
            </BCardHeader>
            <BCardBody>
              <div class="table-responsive">
                <table class="table table-sm table-centered align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>{{ t('name') }}</th>
                      <th class="text-end">{{ t('driver_dashboard_earnings') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in topByEarnings" :key="row.id">
                      <td><Link :href="`/approved-drivers/view-profile/${row.id}`">{{ row.name }}</Link></td>
                      <td class="text-end">{{ currencySymbol }}{{ Number(row.earnings || 0).toFixed(2) }}</td>
                    </tr>
                    <tr v-if="!topByEarnings.length">
                      <td colspan="2" class="text-muted text-center">{{ t('no_data_found') }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>
    </template>
  </Layout>
</template>
