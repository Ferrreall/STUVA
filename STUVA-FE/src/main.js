import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'
import { createPinia } from 'pinia'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import './assets/css/app-variables.css'
import './style.css'
import { registerSW } from 'virtual:pwa-register'

registerSW({ immediate: true })

createApp(App).use(router).mount('#app')

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.use(Toast)
app.mount('#app')
