<?php

namespace KhanhArtisan\LaravelBackbone\RelationCascade;

enum CascadeStatus: int
{
    case IDLE = 0;
    case DELETING = 1;
    case DELETED = 2;
    case RESTORING = 3;
}