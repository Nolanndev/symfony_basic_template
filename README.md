# Template symfony basique

Ce projet est un template symfony basique à faire tourner avec docker et nginx





```bash
docker run -i --rm \
	--volume "./data/osm:/osm" \
	--volume "./data/gtfs:/gtfs" \
	--volume "./data/gtfs-out:/gtfs-out" \
	ghcr.io/ad-freiburg/pfaedle:latest \
	-x /osm/idf-france.osm -i /gtfs/gtfs-bievre.zip \
	--inplace \
	--keep-additional-gtfs-fields
```

**Pfaedle manual**
```plain text
WARNING: The requested image's platform (linux/amd64) does not match the detected host platform (linux/arm64/v8) and no specific platform was requested
pfaedle GTFS map matcher -128-NOTFOUND-dirty
(built Jan  9 2026 15:38:16 with geometry precision <double>)

(C) 2026 University of Freiburg - Chair of Algorithms and Data Structures
Authors: Patrick Brosi <brosi@informatik.uni-freiburg.de>

Usage: /usr/local/bin/pfaedle -x <OSM FILE> <GTFS FEED>

Allowed options:

General:
  -v [ --version ]                 print version
  -h [ --help ]                    show this help message
  -D [ --drop-shapes ]             drop shapes already present in the feed and
                                     recalculate them
  --write-colors                   write matched route line colors, where missing

Input:
  -c [ --config ] arg              pfaedle config file
  -i [ --input ] arg               gtfs feed(s), may also be given as positional
  -F [ --keep-additional-gtfs-fields ] argkeep additional non-standard feeds in GTFS input
                                     parameter (see usage)
  -x [ --osm-file ] arg            OSM input file, as XML (.osm, .osm.bz2, .osm.gz) or PBF (.pbf)
  -m [ --mots ] arg (=all)         MOTs to calculate shapes for, comma sep.,
                                     either as string {all, tram | streetcar,
                                     subway | metro, rail | train, bus,
                                     ferry | boat | ship, cablecar, gondola,
                                     funicular, coach, mono-rail | monorail,
                                     trolley | trolleybus | trolley-bus} or
                                     as GTFS mot codes

Output:
  -o [ --output ] arg (=gtfs-out)  GTFS output path
  -X [ --osm-out ] arg             if specified, a filtered OSM file will be
                                     written to <arg>, as XML (.osm, .osm.bz2, .osm.gz) or PBF (.pbf)
  --inplace                        overwrite input GTFS feed with output feed

Debug Output:
  -d [ --dbg-path ] arg (=.)       output path for debug files
  --write-trgraph                  write transit graph as GeoJSON to
                                     <dbg-path>/trgraph.json
  --write-graph                    write routing graph as GeoJSON to
                                     <dbg-path>/graph.json

Misc:
  -T [ --trip-id ] arg             Do routing only for trip <arg>, write result 
                                     to <dbg-path>/path.json
  --overpass                       Output overpass query for matching OSM data
  --osmfilter                      Output osmfilter filter rules for matching OSM data
  -g [ --grid-size ] arg (=2000)   Approx. grid cell size in meters
  -b [ --box-padding ] arg (=20000)Padding of bounding box used to crop input OSM data in meters
  --no-fast-hops                   Disable fast hops technique
  --no-a-star                      Disable A* heuristic 
  --no-trie                        Disable trip tries 
  --no-hop-cache                   Disable hop cache 
  --stats                          write stats to stats.json
  -W [ --warn ]                    enable verbose warning messages
  -P                               additional parameter string (in cfg file format)
```


## Loom

```bash
gtfs2graph -m all /data/gtfs_files/{$filebase}.zip > /data/temp/{$filebase}.raw.json
topo -d 100 --infer-restr-max-dist 2 --write-stats --max-comp-dist 10 < /data/temp/{$filebase}.raw.json > /data/temp/{$filebase}.topo.z7-13.temp.json
loom < /data/temp/{$filebase}.topo.z7-13.json > /data/temp/{$filebase}.loom.z7-13.json\"";
```
