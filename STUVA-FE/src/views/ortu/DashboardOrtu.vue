<template>
  <div class="dashboard-container">
    <!-- Header -->
    <header class="header">
      <div class="header-content">
        <div>
          <h1 class="user-name">{{ parent.name }}</h1>
          <p class="user-info">Orang Tua {{ parent.studentName }}</p>
        </div>
        <div class="pwa-badge">
          <Wifi class="icon-sm text-green" />
          <span>PWA Ready</span>
        </div>
      </div>
    </header>

    <main class="main-content">
      <!-- Info Siswa Card -->
      <section class="card">
        <h2 class="card-title">Informasi Siswa</h2>
        <div class="student-info-grid">
          <div class="info-item">
            <span class="info-label">Nama</span>
            <span class="info-value">{{ parent.studentName }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Kelas</span>
            <span class="info-value">{{ parent.studentClass }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">NISN</span>
            <span class="info-value">{{ parent.studentNISN }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Kehadiran</span>
            <span class="info-value" :class="parent.attendancePercentage < 90 ? 'text-red' : 'text-green'">
              {{ parent.attendancePercentage }}%
            </span>
          </div>
        </div>
      </section>

      <!-- Pengajuan Menunggu Persetujuan -->
      <section class="card">
        <div class="section-header">
          <h2 class="card-title">Menunggu Persetujuan</h2>
          <span v-if="pendingRequests.length > 0" class="badge-count">
            {{ pendingRequests.length }}
          </span>
        </div>

        <div v-if="pendingRequests.length === 0" class="empty-state">
          <CheckCircle class="icon-lg text-green" />
          <p class="empty-text">Semua pengajuan telah diproses</p>
        </div>

        <div v-else class="request-list">
          <div 
            v-for="request in pendingRequests" 
            :key="request.id" 
            class="request-item pending-item"
          >
            <div class="request-header">
              <div class="request-type-badge" :class="`badge-${request.type}`">
                {{ request.type.toUpperCase() }}
              </div>
              <div class="request-status-badge status-pending">
                <span class="status-dot"></span>
                <span>Menunggu</span>
              </div>
            </div>
            
            <div class="request-body">
              <div class="request-date">
                <Calendar class="icon-sm text-gray" />
                <span>{{ formatDateRange(request.startDate, request.endDate) }}</span>
              </div>
              <p class="request-description">{{ request.description }}</p>
              
              <div v-if="request.photo" class="request-photo">
                <img :src="request.photo" alt="Bukti" @click="openImageModal(request.photo)" />
              </div>
            </div>

            <div class="request-footer">
              <span class="request-time">Diajukan {{ request.createdAt }}</span>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
              <button 
                @click="openRejectModal(request)" 
                class="btn btn-reject"
                :disabled="processingId === request.id"
              >
                <X class="icon-sm" />
                <span>Tolak</span>
              </button>
              <button 
                @click="approveRequest(request.id)" 
                class="btn btn-approve"
                :disabled="processingId === request.id"
              >
                <CheckCircle class="icon-sm" />
                <span>{{ processingId === request.id ? 'Memproses...' : 'Setujui' }}</span>
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Riwayat Pengajuan -->
      <section class="card">
        <h2 class="card-title">Riwayat Pengajuan</h2>
        
        <div v-if="processedRequests.length === 0" class="empty-state">
          <FileText class="icon-lg text-gray" />
          <p class="empty-text">Belum ada riwayat</p>
        </div>

        <div v-else class="request-list">
          <div 
            v-for="request in processedRequests" 
            :key="request.id" 
            class="request-item"
          >
            <div class="request-header">
              <div class="request-type-badge" :class="`badge-${request.type}`">
                {{ request.type.toUpperCase() }}
              </div>
              <div class="request-status-badge" :class="`status-${request.status}`">
                <span class="status-dot"></span>
                <span>{{ getStatusLabel(request.status) }}</span>
              </div>
            </div>
            
            <div class="request-body">
              <div class="request-date">
                <Calendar class="icon-sm text-gray" />
                <span>{{ formatDateRange(request.startDate, request.endDate) }}</span>
              </div>
              <p class="request-description">{{ request.description }}</p>
              
              <div v-if="request.photo" class="request-photo">
                <img :src="request.photo" alt="Bukti" @click="openImageModal(request.photo)" />
              </div>

              <div v-if="request.status === 'rejected' && request.rejectionNote" class="rejection-note">
                <AlertTriangle class="icon-sm text-red" />
                <div>
                  <p class="rejection-label">Alasan Penolakan:</p>
                  <p class="rejection-text">{{ request.rejectionNote }}</p>
                </div>
              </div>
            </div>

            <div class="request-footer">
              <span class="request-time">Diproses {{ request.processedAt }}</span>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Modal Penolakan -->
    <Teleport to="body">
      <div v-if="showRejectModal" class="modal-overlay" @click="closeRejectModal">
        <div class="modal-container" @click.stop>
          <div class="modal-header">
            <h3 class="modal-title">Alasan Penolakan</h3>
            <button @click="closeRejectModal" class="btn-close">
              <X class="icon-sm" />
            </button>
          </div>

          <form @submit.prevent="rejectRequest" class="modal-body">
            <div class="form-group">
              <label for="rejectionNote" class="form-label">
                Tuliskan alasan penolakan
              </label>
              <textarea 
                id="rejectionNote" 
                v-model="rejectionNote" 
                class="form-textarea"
                rows="4"
                placeholder="Contoh: Tidak ada bukti surat keterangan dokter..."
                required
              ></textarea>
            </div>

            <div class="modal-footer">
              <button type="button" @click="closeRejectModal" class="btn btn-cancel">
                Batal
              </button>
              <button type="submit" class="btn btn-submit btn-danger" :disabled="isProcessing">
                {{ isProcessing ? 'Memproses...' : 'Kirim Penolakan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Gambar -->
    <Teleport to="body">
      <div v-if="showImageModal" class="modal-overlay" @click="closeImageModal">
        <div class="image-modal-container" @click.stop>
          <button @click="closeImageModal" class="btn-close-image">
            <X class="icon-md" />
          </button>
          <img :src="selectedImage" alt="Bukti" class="modal-image" />
        </div>
      </div>
    </Teleport>

    <!-- Toast Notification -->
    <Teleport to="body">
      <div v-if="showToast" class="toast-notification" :class="toastType">
        <div class="toast-content">
          <CheckCircle v-if="toastType === 'success'" class="icon-sm" />
          <AlertTriangle v-else class="icon-sm" />
          <span>{{ toastMessage }}</span>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { 
  Wifi, 
  AlertTriangle, 
  X, 
  CheckCircle, 
  Calendar,
  FileText
} from 'lucide-vue-next'

const showRejectModal = ref(false)
const showImageModal = ref(false)
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')
const rejectionNote = ref('')
const selectedRequestId = ref(null)
const selectedImage = ref(null)
const isProcessing = ref(false)
const processingId = ref(null)

const parent = ref({
  name: 'Bapak/Ibu Orang Tua',
  studentName: 'Siswa Test',
  studentClass: 'XII RPL 1',
  studentNISN: '1234567890',
  attendancePercentage: 88
})

// Data pengajuan dari siswa (simulasi - nanti dari API)
const allRequests = ref([
  {
    id: 1,
    type: 'sakit',
    startDate: '2026-09-03',
    endDate: '2026-09-04',
    description: 'Demam tinggi dan batuk',
    photo: null,
    status: 'pending',
    createdAt: '2 jam yang lalu',
    processedAt: null,
    rejectionNote: null
  },
  {
    id: 2,
    type: 'izin',
    startDate: '2026-08-28',
    endDate: '2026-08-28',
    description: 'Acara keluarga',
    photo: null,
    status: 'pending',
    createdAt: '5 hari yang lalu',
    processedAt: null,
    rejectionNote: null
  },
  {
    id: 3,
    type: 'sakit',
    startDate: '2026-08-20',
    endDate: '2026-08-21',
    description: 'Demam tinggi dan flu',
    photo: null,
    status: 'approved',
    createdAt: '2 minggu yang lalu',
    processedAt: '2 minggu yang lalu',
    rejectionNote: null
  },
  {
    id: 4,
    type: 'dispen',
    startDate: '2026-08-15',
    endDate: '2026-08-15',
    description: 'Mengikuti lomba coding',
    photo: null,
    status: 'rejected',
    createdAt: '3 minggu yang lalu',
    processedAt: '3 minggu yang lalu',
    rejectionNote: 'Tidak ada bukti surat dari penyelenggara lomba'
  }
])

// Filter pending requests
const pendingRequests = computed(() => {
  return allRequests.value.filter(req => req.status === 'pending')
})

// Filter processed requests
const processedRequests = computed(() => {
  return allRequests.value.filter(req => req.status !== 'pending')
})

const displayToast = (message, type = 'success') => {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true
  
  setTimeout(() => {
    showToast.value = false
  }, 3000)
}

const openRejectModal = (request) => {
  selectedRequestId.value = request.id
  rejectionNote.value = ''
  showRejectModal.value = true
}

const closeRejectModal = () => {
  showRejectModal.value = false
  selectedRequestId.value = null
  rejectionNote.value = ''
}

const openImageModal = (imageUrl) => {
  selectedImage.value = imageUrl
  showImageModal.value = true
}

const closeImageModal = () => {
  showImageModal.value = false
  selectedImage.value = null
}

const approveRequest = async (requestId) => {
  processingId.value = requestId
  isProcessing.value = true
  
  try {
    // TODO: Ganti dengan API call sebenarnya
    // await api.post(`/api/ortu/pengajuan/${requestId}/approve`)
    
    // Simulasi delay
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Update status
    const request = allRequests.value.find(req => req.id === requestId)
    if (request) {
      request.status = 'approved'
      request.processedAt = 'Baru saja'
    }
    
    displayToast('Pengajuan berhasil disetujui!', 'success')
    
  } catch (error) {
    displayToast('Gagal menyetujui pengajuan. Silakan coba lagi.', 'error')
    console.error('Error approving request:', error)
  } finally {
    processingId.value = null
    isProcessing.value = false
  }
}

const rejectRequest = async () => {
  isProcessing.value = true
  
  try {
    // TODO: Ganti dengan API call sebenarnya
    // await api.post(`/api/ortu/pengajuan/${selectedRequestId.value}/reject`, {
    //   rejectionNote: rejectionNote.value
    // })
    
    // Simulasi delay
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Update status
    const request = allRequests.value.find(req => req.id === selectedRequestId.value)
    if (request) {
      request.status = 'rejected'
      request.rejectionNote = rejectionNote.value
      request.processedAt = 'Baru saja'
    }
    
    closeRejectModal()
    displayToast('Pengajuan berhasil ditolak.', 'success')
    
  } catch (error) {
    displayToast('Gagal menolak pengajuan. Silakan coba lagi.', 'error')
    console.error('Error rejecting request:', error)
  } finally {
    isProcessing.value = false
  }
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak'
  }
  return labels[status] || status
}

const formatDateRange = (start, end) => {
  const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  }
  
  if (start === end) {
    return formatDate(start)
  }
  return `${formatDate(start)} - ${formatDate(end)}`
}
</script>

<style scoped>
/* Layout Container */
.dashboard-container {
  min-height: 100vh;
  background-color: #f9fafb;
  padding-bottom: 80px;
  color: #1f2937;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.main-content {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Header Styles */
.header {
  background-color: #7c3aed;
  padding: 20px 16px;
  color: #ffffff;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.user-name {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0;
}

.user-info {
  font-size: 0.875rem;
  color: #ede9fe;
  margin: 4px 0 0 0;
}

.pwa-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  border-radius: 9999px;
  background-color: #6d28d9;
  padding: 4px 12px;
  font-size: 0.75rem;
  font-weight: 500;
  color: #ede9fe;
}

/* Card Styling */
.card {
  border-radius: 12px;
  background-color: #ffffff;
  padding: 16px;
  border: 1px solid #f3f4f6;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.card-title {
  margin-bottom: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.badge-count {
  background-color: #ef4444;
  color: #ffffff;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 9999px;
  min-width: 24px;
  text-align: center;
}

/* Student Info Grid */
.student-info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
}

.info-value {
  font-size: 0.875rem;
  color: #1f2937;
  font-weight: 600;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
  gap: 12px;
}

.icon-sm {
  width: 16px;
  height: 16px;
}

.icon-md {
  width: 20px;
  height: 20px;
}

.icon-lg {
  width: 48px;
  height: 48px;
}

.empty-text {
  font-size: 0.875rem;
  color: #9ca3af;
  margin: 0;
}

/* Request List */
.request-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.request-item {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 12px;
  background-color: #fefefe;
  transition: box-shadow 0.2s ease;
}

.pending-item {
  border-color: #fde68a;
  background-color: #fffbeb;
}

.request-item:hover {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.request-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.request-type-badge {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.badge-izin {
  background-color: #dbeafe;
  color: #1e40af;
}

.badge-sakit {
  background-color: #fef3c7;
  color: #92400e;
}

.badge-dispen {
  background-color: #e0e7ff;
  color: #4338ca;
}

.request-status-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-pending {
  background-color: #fef3c7;
  color: #92400e;
}

.status-approved {
  background-color: #d1fae5;
  color: #065f46;
}

.status-rejected {
  background-color: #fee2e2;
  color: #991b1b;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: currentColor;
}

.request-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.request-date {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.813rem;
  color: #4b5563;
  font-weight: 500;
}

.request-description {
  font-size: 0.875rem;
  color: #1f2937;
  line-height: 1.5;
  margin: 0;
}

.request-photo {
  margin-top: 4px;
}

.request-photo img {
  width: 100%;
  max-width: 200px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.request-photo img:hover {
  transform: scale(1.02);
}

.rejection-note {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 10px;
  margin-top: 4px;
}

.rejection-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #991b1b;
  margin: 0 0 2px 0;
}

.rejection-text {
  font-size: 0.75rem;
  color: #b91c1c;
  margin: 0;
  line-height: 1.4;
}

.request-footer {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #f3f4f6;
}

.request-time {
  font-size: 0.7rem;
  color: #9ca3af;
}

/* Action Buttons */
.action-buttons {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
  margin-top: 12px;
}

.btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border-radius: 8px;
  padding: 10px;
  font-weight: 600;
  font-size: 0.813rem;
  border: none;
  cursor: pointer;
  transition: transform 0.1s ease, background-color 0.2s ease;
}

.btn:active:not(:disabled) {
  transform: scale(0.95);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-reject {
  background-color: #ffffff;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.btn-reject:hover:not(:disabled) {
  background-color: #fef2f2;
}

.btn-approve {
  background-color: #22c55e;
  color: #ffffff;
}

.btn-approve:hover:not(:disabled) {
  background-color: #16a34a;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 16px;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-container {
  background-color: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.btn-close:hover {
  background-color: #f3f4f6;
  color: #1f2937;
}

.modal-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

/* Form Styles */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
}

.form-textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #1f2937;
  background-color: #ffffff;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  resize: vertical;
  font-family: inherit;
}

.form-textarea:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.btn-cancel {
  background-color: #ffffff;
  color: #374151;
  border: 1px solid #d1d5db;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.2s ease, border-color 0.2s ease;
}

.btn-cancel:hover {
  background-color: #f9fafb;
  border-color: #9ca3af;
}

.btn-submit {
  background-color: #2563eb;
  color: #ffffff;
  border: none;
  padding: 10px 24px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.1s ease;
}

.btn-submit:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.btn-submit.btn-danger {
  background-color: #ef4444;
}

.btn-submit.btn-danger:hover:not(:disabled) {
  background-color: #dc2626;
}

.btn-submit:active:not(:disabled) {
  transform: scale(0.98);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Image Modal */
.image-modal-container {
  position: relative;
  max-width: 90vw;
  max-height: 90vh;
  animation: slideUp 0.3s ease;
}

.btn-close-image {
  position: absolute;
  top: -40px;
  right: 0;
  background-color: #ffffff;
  border: none;
  color: #1f2937;
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  transition: background-color 0.2s ease;
}

.btn-close-image:hover {
  background-color: #f3f4f6;
}

.modal-image {
  max-width: 100%;
  max-height: 90vh;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
}

/* Toast Notification */
.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  background-color: #ffffff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  z-index: 2000;
  min-width: 300px;
  animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.toast-content {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 0.875rem;
  font-weight: 500;
}

.toast-notification.success {
  border-left: 4px solid #22c55e;
  color: #15803d;
}

.toast-notification.success .icon-sm {
  color: #22c55e;
}

.toast-notification.error {
  border-left: 4px solid #ef4444;
  color: #b91c1c;
}

.toast-notification.error .icon-sm {
  color: #ef4444;
}

/* Color Utilities */
.text-green { color: #16a34a; }
.text-red { color: #dc2626; }
.text-gray { color: #6b7280; }

/* Responsive */
@media (max-width: 640px) {
  .modal-container {
    max-height: 95vh;
    border-radius: 16px 16px 0 0;
  }
  
  .modal-overlay {
    align-items: flex-end;
    padding: 0;
  }
}
</style>
