/**
 * Thermal Printer via Web Bluetooth — ESC/POS commands
 * Supports 58mm thermal receipt printers over BLE SPP.
 */
const ThermalPrinter = {
    DEVICE_NAME: 'Printer',

    /** ESC/POS command bytes */
    CMD: {
        INIT:          [0x1B, 0x40],
        ALIGN_LEFT:    [0x1B, 0x61, 0x00],
        ALIGN_CENTER:  [0x1B, 0x61, 0x01],
        ALIGN_RIGHT:   [0x1B, 0x61, 0x02],
        BOLD_ON:       [0x1B, 0x45, 0x01],
        BOLD_OFF:      [0x1B, 0x45, 0x00],
        UNDERLINE_ON:  [0x1B, 0x2D, 0x01],
        UNDERLINE_OFF: [0x1B, 0x2D, 0x00],
        DOUBLE_HW:     [0x1B, 0x21, 0x30], // double height + width
        DOUBLE_H:      [0x1B, 0x21, 0x10], // double height only
        NORMAL:        [0x1B, 0x21, 0x00],
        CUT_FULL:      [0x1D, 0x56, 0x00],
        CUT_PARTIAL:   [0x1D, 0x56, 0x01],
        FEED:          (n) => [0x1B, 0x64, n || 2],
    },

    /** Standard thermal printer SPP service UUIDs (tried in order) */
    SERVICE_UUIDS: [
        '00001101-0000-1000-8000-00805f9b34fb',
        '000018f0-0000-1000-8000-00805f9b34fb',
        'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
    ],

    _characteristic: null,
    _encoder: new TextEncoder(),

    /** Check if Web Bluetooth is available */
    isSupported() {
        return !!navigator.bluetooth;
    },

    /**
     * Convert string to ESC/POS-encoded bytes with command interleaving.
     * Simple format: plain text with special markers for alignment/bold/divider.
     */
    _encode(content) {
        const result = [];
        const lines = content.split('\n');

        for (const line of lines) {
            if (line === '---') {
                result.push(new Uint8Array([0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D, 0x2D]));
                continue;
            }
            const trimmed = line.replace(/\t/g, '        ');
            result.push(this._encoder.encode(trimmed + '\n'));
        }

        // Flatten into single Uint8Array
        const totalLen = result.reduce((s, a) => s + a.length, 0);
        const out = new Uint8Array(totalLen);
        let offset = 0;
        for (const chunk of result) {
            out.set(chunk, offset);
            offset += chunk.length;
        }
        return out;
    },

    /** Build a formatted 32-char-wide receipt string */
    buildReceipt(data) {
        const W = 32; // 58mm ≈ 32 chars
        const pad = (s, w) => {
            s = String(s);
            return s.length >= w ? s : s + ' '.repeat(w - s.length);
        };
        const center = (s) => {
            s = String(s);
            const left = Math.max(0, Math.floor((W - s.length) / 2));
            return ' '.repeat(left) + s;
        };
        const rpad = (l, r, wl) => {
            const ls = String(l), rs = String(r);
            const ws = wl || Math.floor(W * 0.55);
            return pad(ls.substring(0, ws), ws) + pad(rs, W - ws);
        };

        const lines = [];
        lines.push(center(data.tenant_name || 'TENANT').toUpperCase());
        if (data.tenant_address) lines.push(center(data.tenant_address));
        if (data.tenant_phone) lines.push(center('Tel: ' + data.tenant_phone));
        lines.push('---');
        lines.push(pad('No: ' + data.transaction_number, W));
        lines.push(pad(data.datetime || '', W));
        if (data.cashier) lines.push(pad('Kasir: ' + data.cashier, W));
        lines.push('---');

        for (const item of data.items || []) {
            lines.push(pad(item.name.substring(0, W - 12), W - 12) + rpad('', '', 0).substring(0, 0) || '');
            // quantity x price = subtotal
            const qtyPrice = item.qty + ' x ' + item.price;
            const subtotal = item.subtotal;
            lines.push('  ' + rpad(qtyPrice, subtotal, W - 2));

            if (item.addons && item.addons.length > 0) {
                for (const a of item.addons) {
                    lines.push('    + ' + rpad(a.name, a.price, W - 6));
                }
            }
        }

        lines.push('---');
        lines.push(rpad('TOTAL', data.total, W));
        lines.push(rpad('Bayar (' + (data.payment_method || '-').toUpperCase() + ')', data.total, W));
        lines.push('---');
        lines.push(center('Terima Kasih'));
        lines.push(center('Selamat Datang Kembali'));
        lines.push('');
        lines.push('');
        lines.push('');
        lines.push('');

        return lines.join('\n');
    },

    STORAGE_KEY: 'thermal_printer_name',

    /** Get saved printer name */
    getSavedName() {
        try { return localStorage.getItem(this.STORAGE_KEY); } catch(e) { return null; }
    },

    /** Save printer name */
    saveName(name) {
        try { localStorage.setItem(this.STORAGE_KEY, name); } catch(e) {}
    },

    /** Forget saved printer */
    forget() {
        try { localStorage.removeItem(this.STORAGE_KEY); } catch(e) {}
    },

    /** Scan + pair printer, save name without printing */
    async scanAndSave() {
        if (!this.isSupported()) {
            throw new Error('Web Bluetooth tidak didukung.');
        }
        const device = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: this.SERVICE_UUIDS,
        });
        if (device.name) this.saveName(device.name);
        return device.name;
    },

    /** Try to get a previously granted device silently */
    async _getDevice() {
        // Chrome 85+: getDevices() returns previously granted devices without picker
        if (navigator.bluetooth.getDevices) {
            const devices = await navigator.bluetooth.getDevices();
            const savedName = this.getSavedName();
            if (savedName) {
                const match = devices.find(d => d.name === savedName);
                if (match) return match;
            }
            if (devices.length > 0) return devices[0];
        }
        return null;
    },

    /**
     * Scan + connect + print via Bluetooth.
     * Tries silent reconnect first, falls back to device picker.
     */
    async print(data, opts = {}) {
        if (!this.isSupported()) {
            alert('Web Bluetooth tidak didukung di browser ini.\nGunakan Chrome/Edge di desktop atau Android.');
            return;
        }

        const onStatus = opts.onStatus || ((s) => console.log('[Printer]', s));

        try {
            // 1. Try silent reconnect
            let device = await this._getDevice();

            // 2. Fallback: prompt user to pick
            if (!device) {
                const savedName = this.getSavedName();
                onStatus(savedName ? 'Pilih printer (' + savedName + ')...' : 'Pilih printer Bluetooth...');
                device = await navigator.bluetooth.requestDevice({
                    acceptAllDevices: true,
                    optionalServices: this.SERVICE_UUIDS,
                });
            } else {
                onStatus('Menyambung ke ' + device.name + '...');
            }

            if (device.name) this.saveName(device.name);
            onStatus('Menghubungkan ke ' + device.name + '...');
            const server = await device.gatt.connect();

            let service = null;
            for (const uuid of this.SERVICE_UUIDS) {
                try {
                    service = await server.getPrimaryService(uuid);
                    if (service) break;
                } catch (e) { /* try next */ }
            }

            if (!service) {
                // Fallback: try any available service
                try {
                    const services = await server.getServices();
                    if (services.length > 0) service = services[0];
                } catch (e) {
                    throw new Error('Tidak dapat menemukan service printer. Pastikan printer kompatibel dengan SPP BLE.');
                }
            }

            const characteristics = await service.getCharacteristics();
            if (characteristics.length === 0) {
                throw new Error('Tidak dapat menemukan characteristic untuk menulis data.');
            }

            // Find writable characteristic
            let charac = null;
            for (const c of characteristics) {
                if (c.properties.write || c.properties.writeWithoutResponse) {
                    charac = c;
                    break;
                }
            }
            if (!charac) {
                throw new Error('Characteristic printer tidak mendukung write.');
            }

            onStatus('Mengirim data ke printer...');

            const content = this.buildReceipt(data);
            const bytes = this._encode(content);

            // Send in chunks (some printers have MTU limits)
            const MTU = 200;
            for (let i = 0; i < bytes.length; i += MTU) {
                const chunk = bytes.slice(i, Math.min(i + MTU, bytes.length));
                try {
                    await charac.writeValueWithoutResponse(chunk);
                } catch (e) {
                    await charac.writeValue(chunk);
                }
                await new Promise(r => setTimeout(r, 50)); // throttle
            }

            onStatus('Berhasil mencetak! ✅');
        } catch (err) {
            if (err.name === 'NotFoundError' || err.message.includes('cancelled')) {
                onStatus('Pemindaian dibatalkan.');
                return;
            }
            onStatus('Gagal: ' + err.message);
            console.error('[ThermalPrinter]', err);
        }
    }
};
