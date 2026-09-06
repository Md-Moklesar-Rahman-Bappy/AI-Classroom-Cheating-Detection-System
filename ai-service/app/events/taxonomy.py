TAXONOMY = {
    "D1": {
        "code": "D1",
        "name": "Person Detected",
        "category": "detection",
        "label": "Person Detected",
        "color": "green",
    },
    "D2": {
        "code": "D2",
        "name": "Mobile Phone Detected",
        "category": "detection",
        "label": "Mobile Phone Detected",
        "color": "blue",
    },
    "D3": {
        "code": "D3",
        "name": "Multiple Persons Detected",
        "category": "detection",
        "label": "Multiple Persons Detected",
        "color": "yellow",
    },
    "B1": {
        "code": "B1",
        "name": "Repeated Looking Left",
        "category": "behavior",
        "label": "Looking Left",
        "color": "orange",
    },
    "B2": {
        "code": "B2",
        "name": "Repeated Looking Right",
        "category": "behavior",
        "label": "Looking Right",
        "color": "orange",
    },
    "B3": {
        "code": "B3",
        "name": "Looking Backward",
        "category": "behavior",
        "label": "Looking Backward",
        "color": "orange",
    },
    "B4": {
        "code": "B4",
        "name": "Possible Seat Departure",
        "category": "behavior",
        "label": "Possible Seat Departure",
        "color": "red",
    },
    "B5": {
        "code": "B5",
        "name": "Excessive Head Movement",
        "category": "behavior",
        "label": "Excessive Head Movement",
        "color": "orange",
    },
    "S1": {
        "code": "S1",
        "name": "Normal",
        "category": "system",
        "label": "Normal",
        "color": "green",
    },
    "S2": {
        "code": "S2",
        "name": "Insufficient Evidence",
        "category": "system",
        "label": "Insufficient Evidence",
        "color": "gray",
    },
    "S3": {
        "code": "S3",
        "name": "Tracking Lost",
        "category": "system",
        "label": "Tracking Lost",
        "color": "gray",
    },
}

EVENT_CODES = list(TAXONOMY.keys())
CATEGORY_MAP = {k: v["category"] for k, v in TAXONOMY.items()}


def get_category(code: str) -> str:
    return CATEGORY_MAP.get(code, "unknown")


def get_label(code: str) -> str:
    return TAXONOMY.get(code, {}).get("label", code)


def get_name(code: str) -> str:
    return TAXONOMY.get(code, {}).get("name", code)
