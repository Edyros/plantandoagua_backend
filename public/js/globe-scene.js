const TOP_R = 2.58

export function buildSky(THREE) {
    const mat = new THREE.ShaderMaterial({
        side: THREE.BackSide,
        depthWrite: false,
        fog: false,
        vertexShader: `
            varying vec3 vDir;
            void main() {
                vDir = position;
                gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
            }
        `,
        fragmentShader: `
            varying vec3 vDir;
            void main() {
                vec3 dir = normalize(vDir);
                float h = dir.y;
                vec3 top = vec3(0.45, 0.70, 0.93);
                vec3 mid = vec3(0.74, 0.87, 0.97);
                vec3 hor = vec3(0.96, 0.97, 0.95);
                vec3 col = mix(hor, mid, smoothstep(-0.12, 0.28, h));
                col = mix(col, top, smoothstep(0.2, 0.85, h));
                vec3 sunDir = normalize(vec3(-0.35, 0.72, 0.42));
                float sun = pow(max(dot(dir, sunDir), 0.0), 280.0);
                float glow = pow(max(dot(dir, sunDir), 0.0), 6.0);
                col += vec3(1.0, 0.97, 0.9) * sun;
                col += vec3(1.0, 0.93, 0.78) * glow * 0.28;
                float puff = sin(dir.x * 6.0 + dir.z * 4.0) * sin(dir.y * 8.0 + 1.2);
                float cloud = smoothstep(0.55, 0.9, puff) * smoothstep(-0.15, 0.25, h);
                col = mix(col, vec3(1.0), cloud * 0.72);
                gl_FragColor = vec4(col, 1.0);
            }
        `,
    })
    const sky = new THREE.Mesh(new THREE.SphereGeometry(180, 24, 16), mat)
    sky.frustumCulled = false
    return sky
}

export function buildWorld(THREE, lite) {
    const group = new THREE.Group()
    const origin = new THREE.Vector3(2.15, -0.05, 0)
    group.position.copy(origin)
    const shaders = []

    const island = buildIsland(THREE, lite)
    island.castShadow = !lite
    island.receiveShadow = !lite
    group.add(island)

    const pool = buildPool(THREE, shaders)
    group.add(pool)

    const fall = buildWaterfall(THREE)
    if (fall) group.add(fall)

    const forest = buildForest(THREE, lite)
    forest.trunks.castShadow = !lite
    forest.canopies.castShadow = !lite
    if (forest.tops) forest.tops.castShadow = !lite
    group.add(forest.trunks, forest.canopies)
    if (forest.tops) group.add(forest.tops)

    const tree = buildHeroTree(THREE)
    placeOnTop(tree, -0.72, 0.15)
    tree.scale.setScalar(1.25)
    group.add(tree)

    const sign = buildSign(THREE)
    placeOnTop(sign, -0.22, 0.48)
    sign.scale.setScalar(0.85)
    sign.rotation.y = 0.4
    group.add(sign)

    const house = buildHouse(THREE)
    placeOnTop(house, 1.05, 0.05)
    house.scale.setScalar(0.58)
    house.rotation.y = -0.7
    group.add(house)

    const rocks = buildRocks(THREE, lite ? 4 : 6)
    rocks.forEach((rock) => group.add(rock))

    const clouds = buildClouds(THREE, lite ? 5 : 8)
    group.add(clouds)

    const shade = new THREE.Mesh(
        new THREE.CircleGeometry(3.4, 32),
        new THREE.MeshBasicMaterial({ color: 0x1a3a28, transparent: true, opacity: 0.16, depthWrite: false }),
    )
    shade.rotation.x = -Math.PI / 2
    shade.position.y = -2.7
    group.add(shade)

    return {
        group,
        origin,
        shaders,
        marks: { tree, sign, house },
        tick(time) {
            clouds.children.forEach((cloud, index) => {
                cloud.position.x = cloud.userData.x + Math.sin(time * 0.08 + index) * 0.18
                cloud.position.y = cloud.userData.y + Math.sin(time * 0.15 + index * 1.3) * 0.06
            })
            if (fall?.material?.map) fall.material.map.offset.y = -time * 0.55
            rocks.forEach((rock, index) => {
                const bob = Math.sin(time * 0.45 + rock.userData.phase) * 0.08
                rock.position.set(rock.userData.x, rock.userData.y + bob, rock.userData.z)
                rock.rotation.y = time * 0.12 + index
            })
        },
    }
}

export function buildStops(THREE, world) {
    const look = world.origin.clone()
    look.x -= 1.85
    look.y += 0.55

    return [
        {
            pos: world.origin.clone().add(new THREE.Vector3(0.35, 4.1, 10.8)),
            target: look,
        },
        frameMark(THREE, world, world.marks.tree, 4.4, 1.15),
        frameMark(THREE, world, world.marks.sign, 2.7, 0.55),
        {
            pos: world.origin.clone().add(new THREE.Vector3(-1.4, 5.6, 9.4)),
            target: look.clone(),
        },
        frameMark(THREE, world, world.marks.house, 4.6, 1.7),
        {
            pos: world.origin.clone().add(new THREE.Vector3(0.7, 3.4, 11.4)),
            target: look.clone(),
        },
    ]
}

function frameMark(THREE, world, mark, distance, lift) {
    const spot = new THREE.Vector3()
    mark.getWorldPosition(spot)
    const outward = spot.clone().sub(world.origin)
    outward.y *= 0.35
    if (outward.lengthSq() < 0.01) outward.set(0, 0.2, 1)
    outward.normalize()
    const pos = spot.clone().add(outward.multiplyScalar(distance))
    pos.y += lift
    return { pos, target: spot }
}

function buildIsland(THREE, lite) {
    const segs = lite ? 48 : 80
    const rings = lite ? 16 : 28
    const cliffRings = lite ? 10 : 16
    const positions = []
    const colors = []
    const indices = []
    const grass = new THREE.Color('#3f9a46')
    const moss = new THREE.Color('#67b85a')
    const soil = new THREE.Color('#6d8a48')
    const rock = new THREE.Color('#b09784')
    const rockDark = new THREE.Color('#7d6758')
    const wet = new THREE.Color('#7f9a62')
    const tint = new THREE.Color()

    function push(x, y, z, color) {
        positions.push(x, y, z)
        colors.push(color.r, color.g, color.b)
        return positions.length / 3 - 1
    }

    const cx = 0
    const cz = 0
    const center = push(cx, topHeight(cx, cz), cz, grass)
    const rows = []
    for (let i = 1; i <= rings; i++) {
        const row = []
        const radius = (i / rings) * TOP_R
        for (let j = 0; j < segs; j++) {
            const a = (j / segs) * Math.PI * 2
            const x = Math.cos(a) * radius
            const z = Math.sin(a) * radius
            const y = topHeight(x, z)
            const edge = smoothstep(1.75, TOP_R, radius)
            const lake = lakeMask(x, z)
            tint.copy(grass).lerp(moss, noise(x * 1.4, z * 1.4))
            tint.lerp(soil, noise(x * 0.6 + 3, z * 0.6) * 0.45)
            tint.lerp(wet, clamp(lake, 0, 1))
            tint.lerp(rock, edge)
            row.push(push(x, y, z, tint))
        }
        rows.push(row)
    }

    for (let j = 0; j < segs; j++) {
        const next = (j + 1) % segs
        indices.push(center, rows[0][j], rows[0][next])
    }
    for (let i = 0; i < rows.length - 1; i++) {
        for (let j = 0; j < segs; j++) {
            const next = (j + 1) % segs
            indices.push(rows[i][j], rows[i + 1][j], rows[i][next])
            indices.push(rows[i][next], rows[i + 1][j], rows[i + 1][next])
        }
    }

    let prev = rows[rows.length - 1]
    for (let i = 1; i <= cliffRings; i++) {
        const t = i / cliffRings
        const row = []
        for (let j = 0; j < segs; j++) {
            const a = (j / segs) * Math.PI * 2
            const n = fbm(Math.cos(a) * 2.4 + t * 2, Math.sin(a) * 2.4)
            const rr = TOP_R * (1 - t * 0.42) * (0.94 + n * 0.1)
            const rimY = topHeight(Math.cos(a) * TOP_R, Math.sin(a) * TOP_R)
            const y = rimY - 0.05 - t * 2.25 + (n - 0.5) * 0.22
            const strata = 0.5 + 0.5 * Math.sin(y * 9.5 + a * 2)
            tint.copy(rock).lerp(rockDark, t * 0.65 + (1 - strata) * 0.25)
            if (strata > 0.72 && t < 0.55) tint.lerp(new THREE.Color('#6f8f58'), 0.35)
            row.push(push(Math.cos(a) * rr, y, Math.sin(a) * rr, tint))
        }
        for (let j = 0; j < segs; j++) {
            const next = (j + 1) % segs
            indices.push(prev[j], row[j], prev[next])
            indices.push(prev[next], row[j], row[next])
        }
        prev = row
    }

    const tip = push(0.05, -2.55, 0.02, rockDark)
    for (let j = 0; j < segs; j++) {
        indices.push(prev[j], tip, prev[(j + 1) % segs])
    }

    const geo = new THREE.BufferGeometry()
    geo.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3))
    geo.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3))
    geo.setIndex(indices)
    geo.computeVertexNormals()
    return new THREE.Mesh(geo, new THREE.MeshStandardMaterial({
        vertexColors: true,
        roughness: 0.86,
        metalness: 0,
    }))
}

function buildPool(THREE, shaders) {
    const mesh = new THREE.Mesh(
        new THREE.CircleGeometry(0.72, 36),
        new THREE.MeshPhysicalMaterial({
            color: 0x1f8fbe,
            roughness: 0.08,
            metalness: 0.02,
            clearcoat: 0.7,
            transparent: true,
            opacity: 0.92,
        }),
    )
    mesh.rotation.x = -Math.PI / 2
    mesh.position.set(0.22, topHeight(0.22, 0.82) + 0.045, 0.82)
    attachTime(mesh.material, shaders, `
        transformed.z += sin(uTime * 1.4 + position.x * 6.0 + position.y * 5.0) * 0.012;
    `)
    return mesh
}

function buildWaterfall(THREE) {
    const x = 0.28
    const z0 = 1.28
    const y0 = topHeight(0.22, 0.82) + 0.02
    const segments = 8
    const positions = []
    const uvs = []
    const indices = []
    for (let i = 0; i <= segments; i++) {
        const t = i / segments
        const y = y0 - t * 2.15
        const z = z0 + t * 0.28 + Math.sin(t * Math.PI) * 0.05
        const half = 0.16 + t * 0.05
        positions.push(x - half, y, z, x + half, y, z)
        uvs.push(0, 1 - t, 1, 1 - t)
        if (i < segments) {
            const a = i * 2
            indices.push(a, a + 2, a + 1, a + 1, a + 2, a + 3)
        }
    }
    const geo = new THREE.BufferGeometry()
    geo.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3))
    geo.setAttribute('uv', new THREE.Float32BufferAttribute(uvs, 2))
    geo.setIndex(indices)
    geo.computeVertexNormals()
    const mat = new THREE.MeshBasicMaterial({
        map: fallTexture(THREE),
        transparent: true,
        depthWrite: false,
        opacity: 0.9,
        side: THREE.DoubleSide,
    })
    const mesh = new THREE.Mesh(geo, mat)
    mesh.renderOrder = 2
    return mesh
}

function buildForest(THREE, lite) {
    const count = lite ? 48 : 110
    const trunks = new THREE.InstancedMesh(
        new THREE.CylinderGeometry(0.035, 0.05, 0.36, 6),
        new THREE.MeshStandardMaterial({ color: 0x6a4630, roughness: 0.9 }),
        count,
    )
    const canopies = new THREE.InstancedMesh(
        new THREE.SphereGeometry(0.2, 8, 6),
        new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.7 }),
        count,
    )
    const tops = new THREE.InstancedMesh(
        new THREE.SphereGeometry(0.13, 7, 5),
        new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.66 }),
        count,
    )
    const dummy = new THREE.Object3D()
    const rand = mulberry32(42)
    const clearings = [
        { x: -0.72, z: 0.15, r: 0.42 },
        { x: -0.22, z: 0.48, r: 0.32 },
        { x: 1.05, z: 0.05, r: 0.72 },
        { x: 0.22, z: 0.82, r: 0.85 },
    ]
    let placed = 0
    let guard = 0
    while (placed < count && guard < 4000) {
        guard += 1
        const a = rand() * Math.PI * 2
        const radius = Math.sqrt(rand()) * 2.15
        const x = Math.cos(a) * radius
        const z = Math.sin(a) * radius
        if (clearings.some((spot) => Math.hypot(spot.x - x, spot.z - z) < spot.r)) continue
        if (lakeMask(x, z) > 0.25) continue
        const y = topHeight(x, z)
        const scale = 0.55 + rand() * 0.85
        dummy.position.set(x, y + 0.16 * scale, z)
        dummy.rotation.set(0, rand() * 6, 0)
        dummy.scale.set(scale, scale * (0.85 + rand() * 0.4), scale)
        dummy.updateMatrix()
        trunks.setMatrixAt(placed, dummy.matrix)
        dummy.position.set(x, y + 0.42 * scale, z)
        dummy.scale.set(scale * (0.9 + rand() * 0.35), scale * 0.75, scale * (0.9 + rand() * 0.3))
        dummy.updateMatrix()
        canopies.setMatrixAt(placed, dummy.matrix)
        dummy.position.set(x, y + 0.62 * scale, z)
        dummy.scale.setScalar(scale * 0.72)
        dummy.updateMatrix()
        tops.setMatrixAt(placed, dummy.matrix)
        const tone = rand()
        const color = new THREE.Color(tone > 0.92 ? '#e0c15a' : tone > 0.84 ? '#e08aa0' : tone > 0.4 ? '#3c9a48' : '#1f7a38')
        canopies.setColorAt(placed, color)
        tops.setColorAt(placed, color.clone().offsetHSL(0.02, 0.04, 0.06))
        placed += 1
    }
    trunks.count = placed
    canopies.count = placed
    tops.count = placed
    trunks.instanceMatrix.needsUpdate = true
    canopies.instanceMatrix.needsUpdate = true
    tops.instanceMatrix.needsUpdate = true
    if (canopies.instanceColor) canopies.instanceColor.needsUpdate = true
    if (tops.instanceColor) tops.instanceColor.needsUpdate = true
    trunks.frustumCulled = false
    canopies.frustumCulled = false
    tops.frustumCulled = false
    return { trunks, canopies, tops }
}

function buildHeroTree(THREE) {
    const group = new THREE.Group()
    const trunk = new THREE.Mesh(
        new THREE.CylinderGeometry(0.055, 0.09, 0.72, 7),
        new THREE.MeshStandardMaterial({ color: 0x6b4b32, roughness: 0.86 }),
    )
    trunk.position.y = 0.36
    const leaf = new THREE.MeshStandardMaterial({ color: 0x2f8f3e, roughness: 0.64 })
    const canopy = new THREE.Mesh(new THREE.SphereGeometry(0.36, 14, 10), leaf)
    canopy.position.y = 0.82
    canopy.scale.set(1.05, 0.78, 1)
    const top = new THREE.Mesh(
        new THREE.SphereGeometry(0.24, 12, 8),
        new THREE.MeshStandardMaterial({ color: 0x49b255, roughness: 0.62 }),
    )
    top.position.set(0.05, 1.08, 0.02)
    group.add(trunk, canopy, top)
    return group
}

function buildSign(THREE) {
    const group = new THREE.Group()
    const post = new THREE.Mesh(
        new THREE.CylinderGeometry(0.028, 0.034, 0.58, 6),
        new THREE.MeshStandardMaterial({ color: 0x8a5a36, roughness: 0.8 }),
    )
    post.position.y = 0.29
    const board = new THREE.Mesh(
        new THREE.BoxGeometry(0.46, 0.46, 0.03),
        new THREE.MeshStandardMaterial({ map: qrTexture(THREE), roughness: 0.5 }),
    )
    board.position.y = 0.66
    group.add(post, board)
    return group
}

function buildHouse(THREE) {
    const group = new THREE.Group()
    const wall = new THREE.Mesh(
        new THREE.BoxGeometry(1.5, 0.9, 1.15),
        new THREE.MeshStandardMaterial({ color: 0xf4f0e8, roughness: 0.78 }),
    )
    wall.position.y = 0.5
    const roof = new THREE.Mesh(
        new THREE.ConeGeometry(1.15, 0.55, 4),
        new THREE.MeshStandardMaterial({ color: 0x9a4a38, roughness: 0.7 }),
    )
    roof.geometry.rotateY(Math.PI / 4)
    roof.position.y = 1.15
    const door = new THREE.Mesh(
        new THREE.BoxGeometry(0.28, 0.42, 0.04),
        new THREE.MeshStandardMaterial({ color: 0x5c3a28, roughness: 0.8 }),
    )
    door.position.set(0, 0.28, 0.58)
    group.add(wall, roof, door)
    return group
}

function buildRocks(THREE, count) {
    const homes = [
        [2.5, 1.35, 0.55],
        [2.15, 0.2, -1.35],
        [-1.15, 2.15, -0.7],
        [1.2, 2.45, -1.7],
        [2.7, 1.9, -1.15],
        [-0.15, 0.5, 2.05],
    ]
    const rocks = []
    const rand = mulberry32(9)
    for (let i = 0; i < count; i++) {
        const rock = new THREE.Group()
        const geo = new THREE.IcosahedronGeometry(0.22 + rand() * 0.16, 1)
        const pos = geo.attributes.position
        const colors = new Float32Array(pos.count * 3)
        for (let v = 0; v < pos.count; v++) {
            const y = pos.getY(v)
            pos.setXYZ(
                v,
                pos.getX(v) * (0.85 + rand() * 0.3),
                y * (0.55 + rand() * 0.25),
                pos.getZ(v) * (0.85 + rand() * 0.3),
            )
            const color = new THREE.Color(y > 0.02 ? '#6f9456' : '#a08472')
            colors[v * 3] = color.r
            colors[v * 3 + 1] = color.g
            colors[v * 3 + 2] = color.b
        }
        geo.setAttribute('color', new THREE.BufferAttribute(colors, 3))
        geo.computeVertexNormals()
        rock.add(new THREE.Mesh(geo, new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.88 })))
        if (i % 2 === 0) {
            const tuft = new THREE.Mesh(
                new THREE.SphereGeometry(0.1, 6, 5),
                new THREE.MeshStandardMaterial({ color: 0x3d9a49, roughness: 0.7 }),
            )
            tuft.position.y = 0.16
            tuft.scale.y = 0.7
            rock.add(tuft)
        }
        const home = homes[i]
        rock.position.set(home[0], home[1], home[2])
        rock.userData.x = home[0]
        rock.userData.y = home[1]
        rock.userData.z = home[2]
        rock.userData.phase = rand() * Math.PI * 2
        rocks.push(rock)
    }
    return rocks
}

function buildClouds(THREE, count) {
    const group = new THREE.Group()
    const puff = new THREE.SphereGeometry(1, 14, 10)
    const mat = new THREE.MeshBasicMaterial({
        color: 0xffffff,
        transparent: true,
        opacity: 0.9,
        depthWrite: false,
    })
    const layout = [
        [-6.4, 3.2, -3.4, 1.15],
        [-3.2, 4.2, -4.6, 0.95],
        [0.4, 4.6, -4.2, 1.2],
        [4.2, 3.5, -3.6, 1.05],
        [6.6, 2.2, -2.2, 0.85],
        [-7.2, 1.4, -1.2, 0.9],
        [3.4, 5.1, -1.8, 0.75],
        [-1.6, 5.2, -2.4, 0.8],
    ]
    for (let i = 0; i < count; i++) {
        const [x, y, z, s] = layout[i]
        const cloud = new THREE.Group()
        for (let k = 0; k < 4; k++) {
            const mesh = new THREE.Mesh(puff, mat)
            mesh.position.set((k - 1.5) * 0.72 * s, (k % 2) * 0.22 * s, (k - 1) * 0.12)
            mesh.scale.set(s * (0.75 + (k % 3) * 0.12), s * 0.62, s * 0.8)
            cloud.add(mesh)
        }
        cloud.position.set(x, y, z)
        cloud.userData.x = x
        cloud.userData.y = y
        group.add(cloud)
    }
    return group
}

function placeOnTop(object, x, z) {
    object.position.set(x, topHeight(x, z), z)
}

function topHeight(x, z) {
    const r = Math.hypot(x, z)
    const n = fbm(x * 0.9 + 1.2, z * 0.9 + 0.4)
    const edge = smoothstep(1.65, TOP_R, r)
    return 1.05 + (n - 0.5) * 0.26 - r * r * 0.03 - edge * 0.48 - lakeMask(x, z) * 0.18
}

function lakeMask(x, z) {
    const dx = x - 0.22
    const dz = z - 0.82
    return Math.exp(-(dx * dx + dz * dz) / 0.28)
}

function smoothstep(e0, e1, x) {
    const t = clamp((x - e0) / (e1 - e0), 0, 1)
    return t * t * (3 - 2 * t)
}

function clamp(v, min, max) {
    return Math.max(min, Math.min(max, v))
}

function attachTime(material, bucket, snippet) {
    material.onBeforeCompile = (shader) => {
        shader.uniforms.uTime = { value: 0 }
        shader.vertexShader = `uniform float uTime;\n${shader.vertexShader.replace(
            '#include <begin_vertex>',
            `#include <begin_vertex>\n${snippet}`,
        )}`
        bucket.push(shader)
    }
}

function fallTexture(THREE) {
    const canvas = document.createElement('canvas')
    canvas.width = 64
    canvas.height = 256
    const ctx = canvas.getContext('2d')
    const grad = ctx.createLinearGradient(0, 0, 64, 0)
    grad.addColorStop(0, 'rgba(90,190,214,0)')
    grad.addColorStop(0.5, 'rgba(232,250,255,0.95)')
    grad.addColorStop(1, 'rgba(90,190,214,0)')
    ctx.fillStyle = '#7ed4ea'
    ctx.fillRect(0, 0, 64, 256)
    ctx.fillStyle = grad
    ctx.fillRect(0, 0, 64, 256)
    ctx.fillStyle = 'rgba(255,255,255,0.8)'
    for (let i = 0; i < 16; i++) {
        ctx.fillRect(18 + (i % 5) * 6, i * 18, 3, 26)
    }
    const tex = new THREE.CanvasTexture(canvas)
    tex.wrapS = THREE.RepeatWrapping
    tex.wrapT = THREE.RepeatWrapping
    tex.colorSpace = THREE.SRGBColorSpace
    return tex
}

function qrTexture(THREE) {
    const cells = 21
    const size = 8
    const canvas = document.createElement('canvas')
    canvas.width = cells * size
    canvas.height = cells * size
    const ctx = canvas.getContext('2d')
    const rand = mulberry32(77)
    ctx.fillStyle = '#f7f4ee'
    ctx.fillRect(0, 0, canvas.width, canvas.height)
    const finder = (x, y) => {
        ctx.fillStyle = '#1c2a22'
        ctx.fillRect(x * size, y * size, 7 * size, 7 * size)
        ctx.fillStyle = '#f7f4ee'
        ctx.fillRect((x + 1) * size, (y + 1) * size, 5 * size, 5 * size)
        ctx.fillStyle = '#1c2a22'
        ctx.fillRect((x + 2) * size, (y + 2) * size, 3 * size, 3 * size)
    }
    finder(0, 0)
    finder(cells - 7, 0)
    finder(0, cells - 7)
    ctx.fillStyle = '#1c2a22'
    for (let y = 0; y < cells; y++) {
        for (let x = 0; x < cells; x++) {
            const corner = (x < 8 && y < 8) || (x > cells - 9 && y < 8) || (x < 8 && y > cells - 9)
            if (corner || rand() < 0.55) continue
            ctx.fillRect(x * size, y * size, size - 1, size - 1)
        }
    }
    const tex = new THREE.CanvasTexture(canvas)
    tex.colorSpace = THREE.SRGBColorSpace
    tex.magFilter = THREE.NearestFilter
    tex.minFilter = THREE.NearestFilter
    return tex
}

function fbm(x, z) {
    return noise(x, z) * 0.62 + noise(x * 2.05 + 4, z * 2.05) * 0.26 + noise(x * 4.1 + 9, z * 4.1) * 0.12
}

function hash(ix, iz) {
    let n = Math.imul(ix, 374761393) + Math.imul(iz, 668265263)
    n = Math.imul(n ^ (n >>> 13), 1274126177)
    return ((n ^ (n >>> 16)) >>> 0) / 4294967296
}

function noise(x, z) {
    const x0 = Math.floor(x)
    const z0 = Math.floor(z)
    const fx = x - x0
    const fz = z - z0
    const sx = fx * fx * (3 - 2 * fx)
    const sz = fz * fz * (3 - 2 * fz)
    const n00 = hash(x0, z0)
    const n10 = hash(x0 + 1, z0)
    const n01 = hash(x0, z0 + 1)
    const n11 = hash(x0 + 1, z0 + 1)
    return (n00 * (1 - sx) + n10 * sx) * (1 - sz) + (n01 * (1 - sx) + n11 * sx) * sz
}

function mulberry32(seed) {
    let a = seed
    return function rand() {
        a |= 0
        a = (a + 0x6d2b79f5) | 0
        let t = Math.imul(a ^ (a >>> 15), 1 | a)
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296
    }
}
