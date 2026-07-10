import tkinter as tk
from tkinter import ttk, messagebox
import sqlite3
from datetime import datetime

class AplikasiPertanianFinalWeb:
    def __init__(self, root):
        self.root = root
        self.root.title("Sistem Integrasi Pertanian Modern")
        self.root.geometry("1150x700")
        self.root.configure(bg="#F4F7F6")
        
        self.current_user = "PetaniModern"
        self.current_email = "petani@uns.ac.id"
        
        # State Data Pembayaran
        self.sewa_alat_nama = ""
        self.sewa_tarif_per_hari = 0
        
        self.init_db()
        
        self.style = ttk.Style()
        self.style.theme_use('clam')
        self.style.configure('Treeview', background="#FFFFFF", fieldbackground="#FFFFFF", rowheight=28, font=("Segoe UI", 9))
        self.style.configure('Treeview.Heading', background="#0F5132", foreground="white", font=("Segoe UI", 9, "bold"))

        self.container = tk.Frame(self.root, bg="#F4F7F6")
        self.container.pack(fill="both", expand=True)
        
        self.show_web_login()

    def init_db(self):
        conn = sqlite3.connect('pertanian_final.db')
        cursor = conn.cursor()
        cursor.execute('''CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT, nama_depan TEXT, nama_belakang TEXT, username TEXT UNIQUE, email TEXT, password TEXT)''')
        cursor.execute('''CREATE TABLE IF NOT EXISTS sensor_irigasi (
            id INTEGER PRIMARY KEY AUTOINCREMENT, kode TEXT UNIQUE, lokasi TEXT, debit REAL, tma REAL, suhu REAL, kelembaban REAL, status TEXT, waktu TEXT)''')
        cursor.execute('''CREATE TABLE IF NOT EXISTS forum_diskusi (
            id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, pesan TEXT, waktu TEXT)''')
        cursor.execute('''CREATE TABLE IF NOT EXISTS forum_komentar (
            id INTEGER PRIMARY KEY AUTOINCREMENT, diskusi_id INTEGER, username TEXT, komentar TEXT, waktu TEXT)''')
        
        # Tabel Master Alat
        cursor.execute('''CREATE TABLE IF NOT EXISTS master_alat (
            id INTEGER PRIMARY KEY AUTOINCREMENT, nama_alat TEXT UNIQUE, tarif INTEGER)''')
            
        # Tabel Riwayat Transaksi Pembayaran
        cursor.execute('''CREATE TABLE IF NOT EXISTS pembayaran_alat (
            id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, email TEXT, alat TEXT, durasi INTEGER, total INTEGER, metode TEXT, status TEXT, waktu TEXT)''')
        
        # Seed Data Master Alat awal
        cursor.execute("SELECT COUNT(*) FROM master_alat")
        if cursor.fetchone()[0] == 0:
            data_awal = [
                ("Traktor Modern X", 200000),
                ("Mesin Giling Padi", 150000),
                ("Drone Sprayer Pupuk", 350000)
            ]
            cursor.executemany("INSERT INTO master_alat (nama_alat, tarif) VALUES (?,?)", data_awal)
            
        conn.commit()
        conn.close()

    def clear_container(self):
        for widget in self.container.winfo_children():
            widget.destroy()

    # ==========================================
    # AUTHENTICATION
    # ==========================================
    def show_web_login(self):
        self.clear_container()
        left_panel = tk.Frame(self.container, bg="#0A3622", width=480)
        left_panel.pack(side="left", fill="both")
        left_panel.pack_propagate(False)
        
        tk.Label(left_panel, text="LaduSycn", font=("Segoe UI", 13, "bold"), fg="#A3E635", bg="#0A3622").pack(anchor="w", padx=50, pady=40)
        tk.Label(left_panel, text="Pantau pertanian\nReal-Time", font=("Segoe UI", 26, "bold"), fg="white", bg="#0A3622", justify="left").pack(anchor="w", padx=50, pady=10)
        tk.Label(left_panel, text="Irigasi, Diskusi, dan Sewa Alat.", font=("Segoe UI", 10), fg="#A7F3D0", bg="#0A3622", justify="left").pack(anchor="w", padx=50, pady=10)
        
        right_panel = tk.Frame(self.container, bg="#F4F7F6")
        right_panel.pack(side="right", fill="both", expand=True)
        
        card = tk.Frame(right_panel, bg="white", highlightbackground="#E0E0E0", highlightthickness=1)
        card.place(relx=0.5, rely=0.5, anchor="center", width=420, height=420)
        
        tk.Label(card, text="Selamat Datang", font=("Segoe UI", 18, "bold"), fg="#0A3622", bg="white").pack(pady=(35, 20))
        
        tk.Label(card, text="USERNAME", font=("Segoe UI", 8, "bold"), fg="#4F4F4F", bg="white").pack(anchor="w", padx=40)
        u_entry = tk.Entry(card, font=("Segoe UI", 11), bd=1, relief="solid", bg="#F9F9F9")
        u_entry.pack(fill="x", padx=40, pady=(2, 15))
        
        tk.Label(card, text="PASSWORD", font=("Segoe UI", 8, "bold"), fg="#4F4F4F", bg="white").pack(anchor="w", padx=40)
        p_entry = tk.Entry(card, show="*", font=("Segoe UI", 11), bd=1, relief="solid", bg="#F9F9F9")
        p_entry.pack(fill="x", padx=40, pady=(2, 25))

        def login_action():
            conn = sqlite3.connect('pertanian_final.db')
            res = conn.execute("SELECT username, email FROM users WHERE username=? AND password=?", (u_entry.get(), p_entry.get())).fetchone()
            conn.close()
            if res:
                self.current_user, self.current_email = res[0], res[1]
                self.show_dashboard_layout()
            else:
                messagebox.showerror("Login Gagal", "Username atau Password salah!")

        tk.Button(card, text="➡️ Masuk ke Sistem", font=("Segoe UI", 11, "bold"), bg="#0F5132", fg="white", bd=0, height=2, command=login_action, cursor="hand2").pack(fill="x", padx=40, pady=5)
        tk.Button(card, text="Belum punya akun? Daftar sekarang", font=("Segoe UI", 9, "bold"), bg="white", fg="#0F5132", bd=0, command=self.show_web_register, cursor="hand2").pack(pady=10)

    def show_web_register(self):
        self.clear_container()
        left_panel = tk.Frame(self.container, bg="#0A3622", width=480)
        left_panel.pack(side="left", fill="both")
        left_panel.pack_propagate(False)
        tk.Label(left_panel, text="LaduSycn", font=("Segoe UI", 13, "bold"), fg="#A3E635", bg="#0A3622").pack(anchor="w", padx=50, pady=40)
        tk.Label(left_panel, text="Gabung & Daftarkan\nAkun Baru Anda", font=("Segoe UI", 24, "bold"), fg="white", bg="#0A3622", justify="left").pack(anchor="w", padx=50, pady=10)
        
        right_panel = tk.Frame(self.container, bg="#F4F7F6")
        right_panel.pack(side="right", fill="both", expand=True)
        
        card = tk.Frame(right_panel, bg="white", highlightbackground="#E0E0E0", highlightthickness=1)
        card.place(relx=0.5, rely=0.5, anchor="center", width=440, height=500)
        tk.Label(card, text="Daftar Akun Baru", font=("Segoe UI", 16, "bold"), fg="#0A3622", bg="white").pack(pady=20)
        
        tk.Label(card, text="USERNAME", font=("Segoe UI", 8, "bold"), bg="white").pack(anchor="w", padx=40)
        u_entry = tk.Entry(card, font=("Segoe UI", 10), bd=1, relief="solid")
        u_entry.pack(fill="x", padx=40, pady=5)

        tk.Label(card, text="EMAIL", font=("Segoe UI", 8, "bold"), bg="white").pack(anchor="w", padx=40)
        e_entry = tk.Entry(card, font=("Segoe UI", 10), bd=1, relief="solid")
        e_entry.pack(fill="x", padx=40, pady=5)

        tk.Label(card, text="PASSWORD", font=("Segoe UI", 8, "bold"), bg="white").pack(anchor="w", padx=40)
        p_entry = tk.Entry(card, show="*", font=("Segoe UI", 10), bd=1, relief="solid")
        p_entry.pack(fill="x", padx=40, pady=5)

        def save_user():
            if not u_entry.get() or not e_entry.get() or not p_entry.get(): return
            conn = sqlite3.connect('pertanian_final.db')
            try:
                conn.execute("INSERT INTO users (nama_depan, nama_belakang, username, email, password) VALUES ('','',?,?,?)", (u_entry.get(), e_entry.get(), p_entry.get()))
                conn.commit()
                messagebox.showinfo("Sukses", "Registrasi Berhasil!")
                self.show_web_login()
            except sqlite3.IntegrityError: messagebox.showerror("Error", "Username sudah ada!")
            finally: conn.close()

        tk.Button(card, text="➕ Buat Akun Sekarang", font=("Segoe UI", 10, "bold"), bg="#0F5132", fg="white", bd=0, height=2, command=save_user, cursor="hand2").pack(fill="x", padx=40, pady=15)
        tk.Button(card, text="Kembali ke Halaman Login", font=("Segoe UI", 9, "underline"), bg="white", fg="#7F8C8D", bd=0, command=self.show_web_login, cursor="hand2").pack()

    def show_dashboard_layout(self):
        self.clear_container()
        sidebar = tk.Frame(self.container, bg="#0B0F19", width=230)
        sidebar.pack(side="left", fill="y")
        sidebar.pack_propagate(False)
        
        tk.Label(sidebar, text="🌾 LaduSycn", font=("Segoe UI", 14, "bold"), fg="#A3E635", bg="#0B0F19").pack(pady=25, anchor="w", padx=20)
        
        self.main_workplace = tk.Frame(self.container, bg="#F8FAFC")
        self.main_workplace.pack(side="right", fill="both", expand=True)

        def route(menu):
            for w in self.main_workplace.winfo_children(): w.destroy()
            if menu == "irigasi": self.render_irigasi_page()
            elif menu == "diskusi": self.render_diskusi_page()
            elif menu == "sewa": self.render_sewa_step1()

        btn_config = {"font": ("Segoe UI", 10), "fg": "#F8FAFC", "bg": "#0B0F19", "activebackground": "#1E293B", "activeforeground": "white", "bd": 0, "anchor": "w", "padx": 20, "height": 2, "cursor": "hand2"}
        tk.Button(sidebar, text="📊 Live Data Sensor", command=lambda: route("irigasi"), **btn_config).pack(fill="x", pady=2)
        tk.Button(sidebar, text="💬 Forum Diskusi", command=lambda: route("diskusi"), **btn_config).pack(fill="x", pady=2)
        tk.Button(sidebar, text="💳 Kasir Sewa Alat Tani", command=lambda: route("sewa"), **btn_config).pack(fill="x", pady=2)
        tk.Button(sidebar, text="🚪 Keluar", bg="#0B0F19", fg="#EF4444", font=("Segoe UI", 9, "bold"), bd=0, command=self.show_web_login).pack(side="bottom", fill="x", pady=20)
        route("irigasi")

    # ==========================================
    # MENU 1: MONITORING SENSOR + CRUD
    # ==========================================
    def render_irigasi_page(self):
        self.main_workplace.configure(bg="#F8FAFC")
        top_bar = tk.Frame(self.main_workplace, bg="white", height=60, highlightbackground="#F1F5F9", highlightthickness=1)
        top_bar.pack(fill="x", padx=20, pady=20)
        tk.Label(top_bar, text="Data Monitoring Sensor Real-Time", font=("Segoe UI", 13, "bold"), fg="#0F5132", bg="white").pack(side="left", padx=15, pady=15)
        
        crud_frame = tk.LabelFrame(self.main_workplace, text=" Management Sensor Panel ", font=("Segoe UI", 9, "bold"), bg="white", padx=15, pady=10)
        crud_frame.pack(fill="x", padx=20, pady=5)
        
        tk.Label(crud_frame, text="ID Kode:", bg="white").grid(row=0, column=0, padx=5)
        kode_e = tk.Entry(crud_frame, width=10)
        kode_e.grid(row=0, column=1, padx=5)
        
        tk.Label(crud_frame, text="Nama Lokasi:", bg="white").grid(row=0, column=2, padx=5)
        lokasi_e = tk.Entry(crud_frame, width=25)
        lokasi_e.grid(row=0, column=3, padx=5)

        table_frame = tk.Frame(self.main_workplace, bg="white")
        table_frame.pack(fill="both", expand=True, padx=20, pady=10)

        columns = ('kode', 'lokasi', 'debit', 'tma', 'suhu', 'kelembaban', 'status')
        tree = ttk.Treeview(table_frame, columns=columns, show='headings')
        for col in columns: tree.heading(col, text=col.upper())
        tree.pack(fill="both", expand=True)

        def load_data():
            for row in tree.get_children(): tree.delete(row)
            conn = sqlite3.connect('pertanian_final.db')
            for r in conn.execute("SELECT kode, lokasi, debit, tma, suhu, kelembaban, status FROM sensor_irigasi").fetchall():
                tree.insert('', tk.END, values=r)
            conn.close()

        def add_sensor():
            if not kode_e.get() or not lokasi_e.get(): return
            conn = sqlite3.connect('pertanian_final.db')
            try:
                conn.execute("INSERT INTO sensor_irigasi VALUES (NULL, ?, ?, 10.0, 30, 27.0, 65, 'Normal', '12:00:00')", (kode_e.get(), lokasi_e.get()))
                conn.commit()
                load_data()
                kode_e.delete(0, tk.END)
                lokasi_e.delete(0, tk.END)
            except sqlite3.IntegrityError: messagebox.showerror("Gagal", "ID Kode sudah ada!")
            finally: conn.close()

        def delete_sensor():
            selected = tree.selection()
            if not selected: return
            item_id = tree.item(selected[0])['values'][0]
            conn = sqlite3.connect('pertanian_final.db')
            conn.execute("DELETE FROM sensor_irigasi WHERE kode=?", (item_id,))
            conn.commit()
            conn.close()
            load_data()

        tk.Button(crud_frame, text="➕ Add Sensor", bg="#0F5132", fg="white", font=("Segoe UI", 9, "bold"), bd=0, padx=10, command=add_sensor).grid(row=0, column=4, padx=10)
        tk.Button(crud_frame, text="❌ Delete Sensor", bg="#EF4444", fg="white", font=("Segoe UI", 9, "bold"), bd=0, padx=10, command=delete_sensor).grid(row=0, column=5, padx=10)
        load_data()

    # ==========================================
    # MENU 2: FORUM DISKUSI
    # ==========================================
    def render_diskusi_page(self):
        self.main_workplace.configure(bg="#F8FAFC")
        tk.Label(self.main_workplace, text="Forum Diskusi Petani", font=("Segoe UI", 15, "bold"), fg="#1E293B", bg="#F8FAFC").pack(anchor="w", padx=30, pady=15)
        
        input_frame = tk.Frame(self.main_workplace, bg="white", padx=15, pady=15, highlightbackground="#E2E8F0", highlightthickness=1)
        input_frame.pack(fill="x", padx=30, pady=5)
        
        text_post = tk.Entry(input_frame, font=("Segoe UI", 11), bg="#F1F5F9", fg="#1E293B", bd=0, relief="solid")
        text_post.pack(fill="x", pady=5, ipady=10)

        feed_canvas = tk.Canvas(self.main_workplace, bg="#F8FAFC", bd=0, highlightthickness=0)
        scrollbar = ttk.Scrollbar(self.main_workplace, orient="vertical", command=feed_canvas.yview)
        scrollable_frame = tk.Frame(feed_canvas, bg="#F8FAFC")

        scrollable_frame.bind("<Configure>", lambda e: feed_canvas.configure(scrollregion=feed_canvas.bbox("all")))
        feed_canvas.create_window((0, 0), window=scrollable_frame, anchor="nw")
        feed_canvas.configure(yscrollcommand=scrollbar.set)

        feed_canvas.pack(side="left", fill="both", expand=True, padx=30, pady=10)
        scrollbar.pack(side="right", fill="y")

        def reload_posts():
            for w in scrollable_frame.winfo_children(): w.destroy()
            conn = sqlite3.connect('pertanian_final.db')
            posts = conn.execute("SELECT id, username, pesan, waktu FROM forum_diskusi ORDER BY id DESC").fetchall()
            
            for pid, user, msg, date in posts:
                post_box = tk.Frame(scrollable_frame, bg="white", pady=12, padx=15, highlightbackground="#E2E8F0", highlightthickness=1)
                post_box.pack(fill="x", pady=8, expand=True)
                
                tk.Label(post_box, text=f"👤 @{user} • {date}", fg="#0F5132", bg="white", font=("Segoe UI", 10, "bold")).pack(anchor="w")
                tk.Label(post_box, text=msg, fg="#1E293B", bg="white", font=("Segoe UI", 11), wraplength=600, justify="left").pack(anchor="w", pady=5)
                
                comments = conn.execute("SELECT username, komentar, waktu FROM forum_komentar WHERE diskusi_id=?", (pid,)).fetchall()
                for c_user, c_msg, c_time in comments:
                    c_box = tk.Frame(post_box, bg="#F1F5F9", padx=10, pady=5)
                    c_box.pack(fill="x", padx=20, pady=2, anchor="w")
                    tk.Label(c_box, text=f"💬 @{c_user}: {c_msg}", fg="#475569", bg="#F1F5F9", font=("Segoe UI", 9), justify="left", wraplength=550).pack(anchor="w")

                reply_frame = tk.Frame(post_box, bg="white")
                reply_frame.pack(fill="x", pady=8)
                r_entry = tk.Entry(reply_frame, font=("Segoe UI", 9), bg="#F1F5F9", fg="#1E293B", width=50, bd=1, relief="solid")
                r_entry.pack(side="left", padx=5, ipady=3)
                
                def make_send_comment(id_post=pid, entry_widget=r_entry):
                    return lambda: send_comment(id_post, entry_widget)

                tk.Button(reply_frame, text="Komen", bg="#0F5132", fg="white", font=("Segoe UI", 9, "bold"), bd=0, padx=8, command=make_send_comment()).pack(side="left")
            conn.close()

        def send_post():
            if not text_post.get(): return
            conn = sqlite3.connect('pertanian_final.db')
            conn.execute("INSERT INTO forum_diskusi (username, pesan, waktu) VALUES (?,?,?)", (self.current_user, text_post.get(), datetime.now().strftime('%Y-%m-%d %H:%M')))
            conn.commit()
            conn.close()
            text_post.delete(0, tk.END)
            reload_posts()

        def send_comment(post_id, entry_w):
            txt = entry_w.get()
            if not txt: return
            conn = sqlite3.connect('pertanian_final.db')
            conn.execute("INSERT INTO forum_komentar (diskusi_id, username, komentar, waktu) VALUES (?,?,?,?)", (post_id, self.current_user, txt, datetime.now().strftime('%H:%M')))
            conn.commit()
            conn.close()
            reload_posts()

        tk.Button(input_frame, text="Kirim Postingan Utama", bg="#10B981", fg="white", font=("Segoe UI", 9, "bold"), bd=0, padx=12, pady=4, command=send_post).pack(anchor="w", pady=(5,0))
        reload_posts()


    # ==========================================
    # MENU 3: KASIR KETIK / PILIH OTOMATIS + GATEWAY LANGSUNG
    # ==========================================
    def render_sewa_step1(self):
        self.main_workplace.configure(bg="#F8FAFC")
        
        tk.Label(self.main_workplace, text="Sistem Manajemen Kasir & Inventaris Alat Tani", font=("Segoe UI", 14, "bold"), bg="#F8FAFC", fg="#1E293B").pack(anchor="w", padx=20, pady=(15,5))
        
        split_frame = tk.Frame(self.main_workplace, bg="#F8FAFC")
        split_frame.pack(fill="both", expand=True, padx=20, pady=5)
        
        left_panel = tk.Frame(split_frame, bg="white", highlightbackground="#E2E8F0", highlightthickness=1)
        left_panel.pack(side="left", fill="both", expand=True, padx=(0,10), pady=10)
        
        right_panel = tk.Frame(split_frame, bg="white", highlightbackground="#E2E8F0", highlightthickness=1, width=440)
        right_panel.pack(side="right", fill="both", padx=(10,0), pady=10)
        right_panel.pack_propagate(False)
        
        # ------------------------------------------
        # BAGIAN KIRI: PANEL CRUD MASTER DATA ALAT
        # ------------------------------------------
        tk.Label(left_panel, text="🛠️ Master Data / Katalog Inventaris", font=("Segoe UI", 11, "bold"), bg="white", fg="#0F5132").pack(anchor="w", padx=15, pady=10)
        
        form_master = tk.Frame(left_panel, bg="#F8FAFC", padx=10, pady=10, highlightbackground="#E2E8F0", highlightthickness=1)
        form_master.pack(fill="x", padx=15, pady=5)
        
        tk.Label(form_master, text="Nama Alat:", bg="#F8FAFC", font=("Segoe UI", 9, "bold")).grid(row=0, column=0, padx=5, sticky="w")
        m_nama_entry = tk.Entry(form_master, width=18, font=("Segoe UI", 10))
        m_nama_entry.grid(row=0, column=1, padx=5, pady=2)
        
        tk.Label(form_master, text="Tarif (Rp):", bg="#F8FAFC", font=("Segoe UI", 9, "bold")).grid(row=0, column=2, padx=5, sticky="w")
        m_tarif_entry = tk.Entry(form_master, width=12, font=("Segoe UI", 10))
        m_tarif_entry.grid(row=0, column=3, padx=5, pady=2)
        
        tree_frame = tk.Frame(left_panel, bg="white")
        tree_frame.pack(fill="both", expand=True, padx=15, pady=10)
        
        cols = ('id', 'nama_alat', 'tarif')
        tree_alat = ttk.Treeview(tree_frame, columns=cols, show='headings', height=8)
        tree_alat.heading('id', text='ID')
        tree_alat.heading('nama_alat', text='NAMA ALAT TANI')
        tree_alat.heading('tarif', text='HARGA SEWA (Rp)')
        tree_alat.column('id', width=40, anchor="center")
        tree_alat.column('nama_alat', width=180, anchor="w")
        tree_alat.column('tarif', width=120, anchor="e")
        tree_alat.pack(fill="both", expand=True)
        
        def refresh_tabel_alat():
            for row in tree_alat.get_children(): tree_alat.delete(row)
            conn = sqlite3.connect('pertanian_final.db')
            for r in conn.execute("SELECT id, nama_alat, tarif FROM master_alat").fetchall():
                tree_alat.insert('', tk.END, values=(r[0], r[1], f"{r[2]:,}"))
            conn.close()
            
        def cmd_add_alat():
            if not m_nama_entry.get() or not m_tarif_entry.get(): return
            conn = sqlite3.connect('pertanian_final.db')
            try:
                conn.execute("INSERT INTO master_alat VALUES (NULL, ?, ?)", (m_nama_entry.get(), int(m_tarif_entry.get())))
                conn.commit()
                refresh_tabel_alat()
                m_nama_entry.delete(0, tk.END)
                m_tarif_entry.delete(0, tk.END)
            except sqlite3.IntegrityError: messagebox.showerror("Gagal", "Nama alat sudah terdaftar!")
            except ValueError: messagebox.showerror("Gagal", "Tarif harus angka!")
            finally: conn.close()
            
        def cmd_delete_alat():
            selected = tree_alat.selection()
            if not selected: return
            val_id = tree_alat.item(selected[0])['values'][0]
            conn = sqlite3.connect('pertanian_final.db')
            conn.execute("DELETE FROM master_alat WHERE id=?", (val_id,))
            conn.commit()
            conn.close()
            refresh_tabel_alat()

        tk.Button(form_master, text="➕ Add", bg="#0F5132", fg="white", font=("Segoe UI", 8, "bold"), bd=0, padx=6, command=cmd_add_alat).grid(row=0, column=4, padx=4)
        tk.Button(form_master, text="❌ Delete", bg="#EF4444", fg="white", font=("Segoe UI", 8, "bold"), bd=0, padx=6, command=cmd_delete_alat).grid(row=0, column=5, padx=4)

        # ------------------------------------------
        # BAGIAN KANAN: PANEL FORM TRANSAKSI + GATEWAY (DIPANGKAS)
        # ------------------------------------------
        tk.Label(right_panel, text="🛒 Form Transaksi Kasir", font=("Segoe UI", 11, "bold"), bg="white", fg="#1E293B").pack(anchor="w", padx=20, pady=10)
        
        tk.Label(right_panel, text="Nama Alat Pertanian", font=("Segoe UI", 9, "bold"), bg="white", fg="#475569").pack(anchor="w", padx=20, pady=(5,2))
        alat_entry = tk.Entry(right_panel, font=("Segoe UI", 10), bd=1, relief="solid")
        alat_entry.pack(fill="x", padx=20, pady=2)
        
        tk.Label(right_panel, text="Harga Sewa (Rp)", font=("Segoe UI", 9, "bold"), bg="white", fg="#475569").pack(anchor="w", padx=20, pady=(5,2))
        tarif_entry = tk.Entry(right_panel, font=("Segoe UI", 10), bd=1, relief="solid")
        tarif_entry.pack(fill="x", padx=20, pady=2)
        
        # Pilihan Gateway Pembayaran Langsung di Bawah Input
        tk.Label(right_panel, text="Pilih Metode Pembayaran", font=("Segoe UI", 10, "bold"), bg="white", fg="#1E293B").pack(anchor="w", padx=20, pady=(15,5))
        
        method_var = tk.StringVar(value="DANA")
        methods = [
            ("🔹 Transfer BCA (8830-1234-567 a/n TERRALEASE)", "BCA"),
            ("📱 GoPay (Bayar instan via Gojek)", "GoPay"),
            ("💚 DANA (Konfirmasi otomatis tanpa upload)", "DANA")
        ]

        for label_text, code in methods:
            rb_frame = tk.Frame(right_panel, bg="white", highlightbackground="#E2E8F0", highlightthickness=1, pady=3)
            rb_frame.pack(fill="x", padx=20, pady=3)
            tk.Radiobutton(rb_frame, text=label_text, value=code, variable=method_var, bg="white", justify="left", font=("Segoe UI", 8)).pack(anchor="w", padx=5)

        # Trigger Klik Tabel Master Alat
        def on_tabel_klik(event):
            selected = tree_alat.selection()
            if not selected: return
            vals = tree_alat.item(selected[0])['values']
            
            alat_entry.delete(0, tk.END)
            alat_entry.insert(0, vals[1])
            
            tarif_bersih = vals[2].replace(",", "")
            tarif_entry.delete(0, tk.END)
            tarif_entry.insert(0, static_clean_number(tarif_bersih))
            
        def static_clean_number(text_val):
            return "".join(c for c in str(text_val) if c.isdigit())
            
        tree_alat.bind("<<TreeviewSelect>>", on_tabel_klik)

        def confirm_and_pay_instantly():
            try:
                self.sewa_alat_nama = alat_entry.get()
                self.sewa_tarif_per_hari = int(tarif_entry.get())
                metode_pilihan = method_var.get()
                
                if not self.sewa_alat_nama or self.sewa_tarif_per_hari < 0:
                    return
                
                # Simpan transaksi ke database
                conn = sqlite3.connect('pertanian_final.db')
                conn.execute("INSERT INTO pembayaran_alat VALUES (NULL, ?, ?, ?, 1, ?, ?, 'Lunas', ?)",
                             (self.current_user, self.current_email, self.sewa_alat_nama, self.sewa_tarif_per_hari, metode_pilihan, datetime.now().strftime('%Y-%m-%d')))
                conn.commit()
                conn.close()
                
                # Lompat ke resi akhir
                self.render_sewa_step3(self.sewa_tarif_per_hari, metode_pilihan)
            except ValueError:
                messagebox.showwarning("Peringatan", "Silakan pilih/isi nama alat dan harga sewa terlebih dahulu!")

        tk.Button(right_panel, text="Konfirmasi & Bayar Sekarang ✨", font=("Segoe UI", 11, "bold"), bg="#0F5132", fg="white", bd=0, height=2, command=confirm_and_pay_instantly, cursor="hand2").pack(fill="x", padx=20, pady=(20,5))
        
        refresh_tabel_alat()

    # STRUK RESI SUKSES AKHIR (PILIHAN METODE DITERIMA SECARA DINAMIS)
    def render_sewa_step3(self, total_harga, metode_pilihan):
        for w in self.main_workplace.winfo_children(): w.destroy()
        
        card = tk.Frame(self.main_workplace, bg="white", highlightbackground="#E2E8F0", highlightthickness=1)
        card.place(relx=0.5, rely=0.5, anchor="center", width=420, height=380)
        
        canvas_check = tk.Canvas(card, width=60, height=60, bg="white", bd=0, highlightthickness=0)
        canvas_check.pack(pady=(40, 10))
        canvas_check.create_oval(5, 5, 55, 55, fill="#10B981", outline="")
        canvas_check.create_line(18, 30, 27, 38, fill="white", width=3)
        canvas_check.create_line(27, 38, 42, 22, fill="white", width=3)

        tk.Label(card, text="Pembayaran Sukses!", font=("Segoe UI", 16, "bold"), bg="white", fg="#1E293B").pack()
        tk.Label(card, text=f"Metode Pembayaran: {metode_pilihan}", font=("Segoe UI", 10), fg="#64748B", bg="white").pack(pady=2)
        
        receipt = tk.Frame(card, bg="#F8FAFC", padx=20, pady=20)
        receipt.pack(fill="x", padx=40, pady=20)
        
        tk.Label(receipt, text=f"Alat: {self.sewa_alat_nama}", font=("Segoe UI", 10), bg="#F8FAFC", fg="#334155").pack(anchor="w", pady=2)
        tk.Label(receipt, text="Durasi: 1 Hari", font=("Segoe UI", 10), bg="#F8FAFC", fg="#334155").pack(anchor="w", pady=2)
        tk.Label(receipt, text="───────────────────────────", bg="#F8FAFC", fg="#E2E8F0").pack(pady=5)
        
        tk.Label(receipt, text="Total Bayar:", font=("Segoe UI", 11), bg="#F8FAFC", fg="#0F5132").pack(side="left")
        tk.Label(receipt, text=f"Rp {total_harga:,}", font=("Segoe UI", 13, "bold"), bg="#F8FAFC", fg="#0F5132").pack(side="right")


if __name__ == '__main__':
    root = tk.Tk()
    app = AplikasiPertanianFinalWeb(root)
    root.mainloop()