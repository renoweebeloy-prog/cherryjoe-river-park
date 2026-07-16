// ==========================================
// 1. SUPABASE INTEGRATION
// ==========================================
const SUPABASE_URL = 'https://gitciqkpxtokouileogg.supabase.co'; 
const SUPABASE_ANON_KEY = 'sb_publishable_XhosUvZiC6AAx7j8BnxcKg_gmiXEtdJ';
const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
let isAdmin = false;

// ==========================================
// 2. CORE LOGIC & REVEAL ENGINE
// ==========================================
const audio = document.getElementById('bgMusic');
const musicBtn = document.getElementById('musicBtn');
const musicIcon = document.getElementById('musicIcon');

window.toggleMusic = function() {
    if (audio.paused) {
        audio.play().then(() => {
            musicIcon.className = "fas fa-pause";
            musicBtn.classList.add('playing');
        }).catch(err => console.log("Audio play blocked by device constraint."));
    } else {
        audio.pause();
        musicIcon.className = "fas fa-play";
        musicBtn.classList.remove('playing');
    }
};

window.handleVideoPlay = function(playingVideo) {
    if (!audio.paused) {
        audio.pause();
        musicIcon.className = "fas fa-play";
        musicBtn.classList.remove('playing');
    }
    document.querySelectorAll('video').forEach(v => {
        if (v !== playingVideo) { v.pause(); }
    });
};

function forceAutoplayOnInteraction() {
    if (audio.paused) {
        audio.play().then(() => {
            musicIcon.className = "fas fa-pause";
            musicBtn.classList.add('playing');
            document.removeEventListener('click', forceAutoplayOnInteraction);
            document.removeEventListener('touchstart', forceAutoplayOnInteraction);
            document.removeEventListener('scroll', forceAutoplayOnInteraction);
        }).catch(e => console.log("Browser block active until explicit interaction."));
    }
}

function initScrollRevealEngine() {
    const targets = document.querySelectorAll('.reveal');
    const visualObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) { entry.target.classList.add('active'); }
        });
    }, { threshold: 0.08, rootMargin: "0px 0px -40px 0px" });
    
    targets.forEach(element => visualObserver.observe(element));
}

// ==========================================
// 3. ROLE SELECTION & LOGIN LOGIC
// ==========================================
window.addEventListener('load', () => {
    initScrollRevealEngine();
    
    const roleOverlay = document.getElementById('roleOverlay');
    
    // Try Autoplay
    const playPromise = audio.play();
    if (playPromise !== undefined) {
        playPromise.then(() => {
            musicIcon.className = "fas fa-pause";
            musicBtn.classList.add('playing');
        }).catch(error => { console.log("Gi-block sa browser ang Autoplay."); });
    }

    // Check if admin is logged in
    checkAdminSession().then(() => {
        if(!isAdmin) {
            roleOverlay.style.display = 'flex';
        }
    });
});

async function checkAdminSession() {
    try {
        const { data: { session } } = await supabase.auth.getSession();
        if (session) {
            setAdminMode(true);
            document.getElementById('roleOverlay').style.display = 'none';
        }
    } catch(error) { console.error(error); }
    fetchAllData();
}

window.selectRole = function(role) {
    if(role === 'user') {
        // Pag-click as GUEST: Hide role overlay, diritso sa Main Site!
        document.getElementById('roleOverlay').style.display = 'none';
        
        // Allow audio upon explicit click
        forceAutoplayOnInteraction();
        
    } else if(role === 'admin') {
        // Pag-click as ADMIN: Hide boxes, show Login Form
        document.getElementById('roleSelectionBoxes').style.display = 'none';
        document.getElementById('adminLoginForm').style.display = 'flex';
    }
};

window.cancelLogin = function() {
    document.getElementById('adminLoginForm').style.display = 'none';
    document.getElementById('roleSelectionBoxes').style.display = 'flex';
}

window.handleLogin = async function() {
    const email = document.getElementById('adminEmail').value;
    const password = document.getElementById('adminPass').value;
    const btn = document.getElementById('loginBtnBtn');
    
    if(!email || !password) return alert("Please enter email and password");

    btn.innerText = "Logging in...";
    try {
        const { error } = await supabase.auth.signInWithPassword({ email, password });
        btn.innerText = "Login";
        if(error) { alert("Login Failed: " + error.message); } 
        else {
            setAdminMode(true);
            document.getElementById('roleOverlay').style.display = 'none';
            window.navigateTo('home', 'nav-home');
            forceAutoplayOnInteraction();
        }
    } catch(err) { btn.innerText = "Login"; }
}

function setAdminMode(status) {
    isAdmin = status;
    document.getElementById('profileBadge').innerText = status ? "Welcome Back!" : "Welcome Visitor!";
    document.getElementById('profileName').innerText = status ? "Admin Account" : "Guest User";
    document.getElementById('profileEmail').innerText = status ? "admin@cherryjoe.com" : "resort-guest@cherryjoe.com";
    document.getElementById('profileRole').innerText = status ? "System Administrator" : "Resort Visitor";
    document.getElementById('logoutBtn').style.display = status ? "block" : "none";
    
    // Show Add/Edit/Delete buttons kung admin
    document.querySelectorAll('.admin-only').forEach(el => el.style.display = status ? 'block' : 'none');
    fetchAllData(); 
}

window.logoutAdmin = async function() {
    await supabase.auth.signOut();
    setAdminMode(false);
    window.location.reload();
}

// ==========================================
// 4. SUPABASE CRUD OPERATIONS
// ==========================================
window.closeModals = function() { document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none'); };

async function fetchAllData() {
    fetchFood(); fetchVideos(); fetchGallery();
}

async function fetchFood() {
    const { data, error } = await supabase.from('food_menu').select('*').order('id', { ascending: true });
    if(error || !data || data.length === 0) return;
    
    const sections = { 'specialties': '', 'combo': '', 'finger': '', 'drinks': '' };
    data.forEach(item => {
        let actionBtns = isAdmin ? `
            <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 5px; z-index: 10;">
                <button style="background: #38bdf8; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;" onclick="window.editFood(${item.id}, '${item.category}', '${item.name.replace(/'/g, "\\'")}', '${item.price}', '${(item.description || '').replace(/'/g, "\\'")}', '${item.image_url || ''}', '${item.status}')"><i class="fas fa-edit"></i></button>
                <button style="background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;" onclick="window.deleteFood(${item.id})"><i class="fas fa-trash"></i></button>
            </div>` : '';
        
        if (item.category === 'drinks') {
            sections['drinks'] += `
                <div class="drink-item">
                    ${actionBtns}
                    <span class="drink-name">${item.name}</span> 
                    <span class="food-price">${item.price}</span>
                </div>`;
        } else if (item.category === 'combo') {
            sections['combo'] += `
                <div class="food-card-with-img" style="flex-direction: row; align-items: center; padding-left: 20px;">
                    ${actionBtns}
                    <i class="fas fa-concierge-bell" style="font-size: 50px; color: #059669;"></i>
                    <div class="food-card-body" style="border-top: none;">
                        <div><h3>${item.name}</h3><p class="desc">${item.description}</p></div>
                        <div class="food-card-footer"><span class="food-price">${item.price}</span><span class="food-status">${item.status}</span></div>
                    </div>
                </div>`;
        } else {
            const imgTag = item.image_url ? `<img src="${item.image_url}" onerror="this.src='https://placehold.co/400x250?text=Food'">` : `<img src="https://placehold.co/400x250?text=No+Image">`;
            const descTag = item.description ? `<p class="desc">${item.description}</p>` : '';
            
            sections[item.category] += `
                <div class="food-card-with-img">
                    ${actionBtns}
                    ${imgTag}
                    <div class="food-card-body">
                        <div><h3>${item.name}</h3>${descTag}</div>
                        <div class="food-card-footer">
                            <span class="food-price">${item.price}</span>
                            <span class="food-status">${item.status}</span>
                        </div>
                    </div>
                </div>`;
        }
    });

    if(sections['specialties']) document.getElementById('grid-specialties').innerHTML = sections['specialties'];
    if(sections['combo']) document.getElementById('grid-combo').innerHTML = sections['combo'];
    if(sections['finger']) document.getElementById('grid-finger').innerHTML = sections['finger'];
    if(sections['drinks']) document.getElementById('grid-drinks').innerHTML = sections['drinks'];
}

window.openAddFoodModal = function() {
    document.getElementById('foodId').value = ''; document.getElementById('foodName').value = ''; document.getElementById('foodPrice').value = '';
    document.getElementById('foodDesc').value = ''; document.getElementById('foodImage').value = ''; document.getElementById('foodStatus').value = 'Available';
    document.getElementById('foodModalHeader').innerText = "Add New Food"; document.getElementById('adminFoodModal').style.display = 'flex';
};

window.editFood = function(id, category, name, price, desc, img, status) {
    document.getElementById('foodId').value = id; document.getElementById('foodCategory').value = category; document.getElementById('foodName').value = name;
    document.getElementById('foodPrice').value = price; document.getElementById('foodDesc').value = desc; document.getElementById('foodImage').value = img;
    document.getElementById('foodStatus').value = status; document.getElementById('foodModalHeader').innerText = "Edit Food Item";
    document.getElementById('adminFoodModal').style.display = 'flex';
};

window.saveFoodItem = async function() {
    const id = document.getElementById('foodId').value;
    const payload = {
        category: document.getElementById('foodCategory').value, name: document.getElementById('foodName').value,
        price: document.getElementById('foodPrice').value, description: document.getElementById('foodDesc').value,
        image_url: document.getElementById('foodImage').value, status: document.getElementById('foodStatus').value
    };
    if(!payload.name || !payload.price) return alert("Name and Price are required.");
    if (id) await supabase.from('food_menu').update(payload).eq('id', id); else await supabase.from('food_menu').insert([payload]);
    window.closeModals(); fetchFood();
};

window.deleteFood = async function(id) { if(confirm("Delete this food item?")) { await supabase.from('food_menu').delete().eq('id', id); fetchFood(); } };

async function fetchVideos() {
    const { data } = await supabase.from('videos').select('*').order('id', { ascending: true });
    if(!data || data.length === 0) return;
    let html = '';
    data.forEach(item => {
        let delBtn = isAdmin ? `<button class="admin-del-btn" style="position: absolute; top: 10px; right: 10px; background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; z-index: 10;" onclick="window.deleteVideo(${item.id})"><i class="fas fa-trash"></i></button>` : '';
        html += `<div class="video-card">
                    ${delBtn}
                    <video controls playsinline preload="metadata" onplay="window.handleVideoPlay(this)"><source src="${item.video_url}" type="video/mp4"></video>
                    <h3>${item.title}</h3>
                 </div>`;
    });
    document.getElementById('video-container').innerHTML = html;
}

window.saveVideo = async function() {
    const payload = { title: document.getElementById('videoTitle').value, video_url: document.getElementById('videoUrl').value };
    if(!payload.title || !payload.video_url) return alert("Required: Title and URL");
    await supabase.from('videos').insert([payload]); window.closeModals(); fetchVideos();
};

window.deleteVideo = async function(id) { if(confirm("Delete this video?")) { await supabase.from('videos').delete().eq('id', id); fetchVideos(); } };

async function fetchGallery() {
    const { data } = await supabase.from('gallery').select('*').order('id', { ascending: true });
    if(!data || data.length === 0) return;
    let html = '';
    data.forEach(item => {
        let delBtn = isAdmin ? `<button class="admin-del-btn" style="position: absolute; top: 5px; right: 5px; background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; z-index: 10;" onclick="window.deletePhoto(${item.id})"><i class="fas fa-trash"></i></button>` : '';
        html += `<div class="gallery-item" style="position:relative;">
                    ${delBtn}
                    <img src="${item.image_url}" alt="Gallery" onclick="window.showImage(this.src)" onerror="this.src='https://placehold.co/150x150?text=Error'">
                 </div>`;
    });
    document.getElementById('gallery-container').innerHTML = html;
}

window.savePhoto = async function() {
    const payload = { image_url: document.getElementById('photoUrl').value };
    if(!payload.image_url) return alert("Required: Image URL");
    await supabase.from('gallery').insert([payload]); window.closeModals(); fetchGallery();
};

window.deletePhoto = async function(id) { if(confirm("Delete this photo?")) { await supabase.from('gallery').delete().eq('id', id); fetchGallery(); } };

// ==========================================
// 5. NAVIGATION AND INTERFACE ENGINE
// ==========================================
window.navigateTo = function(pageId, navItemId) {
    const activePage = document.querySelector('.app-page.page-active');
    const targetPage = document.getElementById('page-' + pageId);
    
    if (activePage && activePage !== targetPage) {
        activePage.style.opacity = '0';
        activePage.style.transform = 'scale(0.98) translateY(12px)';
        setTimeout(() => {
            activePage.classList.remove('page-active');
            targetPage.classList.add('page-active');
            requestAnimationFrame(() => {
                targetPage.style.opacity = '1';
                targetPage.style.transform = 'scale(1) translateY(0)';
            });
        }, 250);
    } else if (!activePage) {
        targetPage.classList.add('page-active');
        targetPage.style.opacity = '1';
        targetPage.style.transform = 'scale(1) translateY(0)';
    }
    
    document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
    if(navItemId && document.getElementById(navItemId)) {
        document.getElementById(navItemId).classList.add('active');
    }
    window.scrollTo({ top: 0 });
};

window.goBack = function() { window.navigateTo('home', 'nav-home'); };

let currentSlideIndex = 0; const slides = document.querySelectorAll('.slide'); const dots = document.querySelectorAll('.dot'); let slideInterval;

window.showSlide = function(index) {
    slides.forEach(slide => slide.classList.remove('active')); dots.forEach(dot => dot.classList.remove('active'));
    if (slides[index]) slides[index].classList.add('active'); if (dots[index]) dots[index].classList.add('active');
    currentSlideIndex = index;
};
window.nextSlide = function() { let targetIndex = (currentSlideIndex + 1) % slides.length; window.showSlide(targetIndex); };
window.changeSlide = function(index) { window.showSlide(index); clearInterval(slideInterval); slideInterval = setInterval(window.nextSlide, 4500); };
slideInterval = setInterval(window.nextSlide, 4500);

window.showImage = function(src) { 
    const box = document.getElementById('lightbox'); document.getElementById('lightbox-img').src = src;
    const downloadBtn = document.getElementById('lightbox-download'); downloadBtn.href = src;
    let filename = src.split('/').pop() || 'CherryJoe_Gallery.jpg';
    downloadBtn.download = filename;
    box.style.display = 'flex'; setTimeout(() => box.classList.add('show'), 15);
};

window.hideImage = function() { const box = document.getElementById('lightbox'); box.classList.remove('show'); setTimeout(() => box.style.display = 'none', 300); };

emailjs.init("xUnFGUm3ZIw6UfW_h");
